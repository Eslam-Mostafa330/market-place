<?php

namespace App\Services\Order;

use App\Enums\DefineStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\Order\OrderPlaced;
use App\Models\Coupon;
use App\Models\CustomerProfile;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StoreBranch;
use App\Models\UserAddress;
use App\Services\Customer\LoyaltyService;
use App\Services\Payment\PaymentService;
use App\Services\Product\ProductStockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class PlaceOrderService
{
    public function __construct(private readonly OrderPricingCalculatorService $calculator, private readonly PaymentService $paymentService, private readonly LoyaltyService $loyaltyService, private readonly OrderNumberGenerator $orderNumberGenerator, private readonly ProductStockService $stockService) {}

    /**
     * Handle the complete order placement workflow including optional payment processing.
     *
     * This method orchestrates the full order lifecycle by:
     * - Validating and resolving related entities (branch, address, coupon)
     * - Locking and resolving order items
     * - Calculating pricing and commissions
     * - Initiating payment when required (e.g. VISA)
     * - Returning both the order and payment details (if applicable)
     * - Handle the wallet discount if exists.
     * - Dispatch the customer preferences job to trigger the preferences logic
     * - Notify the related vendor of the newly created order
     *
     * @param array $data Validated request data
     */
    public function handle(array $data): array
    {
        // Allocated ahead of the transaction so the sequence lock is never held
        // for the length of the order transaction.
        $orderNumber = $this->orderNumberGenerator->generate();

        $order = DB::transaction(function () use ($data, $orderNumber) {
            ['branch' => $branch, 'address' => $address, 'coupon' => $coupon] = $this->validateOrder($data);

            $items = $this->resolveItems($data['items'], $branch);

            $this->ensureCouponMinimumOrder($coupon, $items);

            ['discount' => $walletDiscount, 'profile' => $walletProfile] = $this->resolveWalletDiscount($data);

            $pricing = $this->calculator->calculate($items, $branch, (float) $branch->store->commission_rate, $coupon, $walletDiscount);

            $order = $this->persistOrder($data, $branch, $address, $coupon, $items, $pricing, $orderNumber);

            if ($walletProfile && $pricing['wallet_discount'] > 0) {
                $this->loyaltyService->deductWalletBalance($walletProfile, $pricing['wallet_discount']);
            }

            return $order;
        });

        // Consequences of the order existing — notifying the vendor, refreshing
        // recommendation inputs — are handled by listeners. They run on the queue
        // after commit, so a failing notification can no longer turn a successful
        // placement into a 500 and invite a duplicate retry from the client.
        event(OrderPlaced::from($order));

        return ['order' => $order, 'payment' => $this->handlePayment($order)];
    }

    /**
     * Validate branch, store, address, and coupon.
     * Returns resolved models for use in the order creation process.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
     */
    private function validateOrder(array $data): array
    {
        $branch = StoreBranch::select('id', 'store_id', 'name', 'delivery_fee', 'status')
            ->with(['store:id,name,commission_rate,vendor_profile_id'])
            ->findOrFail($data['store_branch_id']);

        if ($branch->status !== DefineStatus::ACTIVE) {
            throw new UnprocessableEntityHttpException(__('orders.branch_unavailable'));
        }

        $address = UserAddress::select('id', 'user_id', 'address_line_1', 'city', 'state', 'country', 'postal_code', 'additional_info', 'contact_phone', 'latitude', 'longitude')
            ->findOrFail($data['address_id']);

        if ($address->user_id !== auth()->id()) {
            throw new UnprocessableEntityHttpException(__('orders.address_not_owned'));
        }

        $coupon = isset($data['coupon_code'])
            ? $this->validateCoupon($data['coupon_code'], $branch)
            : null;

        return compact('branch', 'address', 'coupon');
    }

    /**
     * Validate coupon eligibility for this order.
     *
     * Checks: active status, store scope, date range, per-user usage limit.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
     */
    private function validateCoupon(string $code, StoreBranch $branch): Coupon
    {
        $coupon = Coupon::select('id', 'store_id', 'status', 'starts_at', 'expires_at', 'usage_limit_per_user', 'coupon_type', 'value', 'minimum_order', 'maximum_discount')
            ->where('code', $code)
            ->lockForUpdate()
            ->firstOrFail();

        if ($coupon->status !== DefineStatus::ACTIVE) {
            throw new UnprocessableEntityHttpException(__('orders.coupon_inactive'));
        }

        if ($coupon->store_id !== null && $coupon->store_id !== $branch->store_id) {
            throw new UnprocessableEntityHttpException(__('orders.coupon_not_valid_per_store'));
        }

        if ($coupon->starts_at && now()->lt($coupon->starts_at)) {
            throw new UnprocessableEntityHttpException(__('orders.coupon_not_active_yet'));
        }

        if ($coupon->expires_at && now()->gt($coupon->expires_at)) {
            throw new UnprocessableEntityHttpException(__('orders.coupon_expired'));
        }

        if ($coupon->usage_limit_per_user) {
            $usedByCustomer = Order::where('customer_id', auth()->id())
                ->where('coupon_id', $coupon->id)
                ->whereNot('order_status', OrderStatus::CANCELLED)
                ->count();

            if ($usedByCustomer >= $coupon->usage_limit_per_user) {
                throw new UnprocessableEntityHttpException(__('orders.coupon_usage_reached'));
            }
        }

        return $coupon;
    }

    /**
     * Enforce the coupon's minimum order value against the resolved items.
     *
     * Checked after items are resolved because the threshold applies to the
     * item subtotal, which is not known while the coupon is first validated.
     *
     * @param Coupon|null $coupon Applied coupon (if any)
     * @param array       $items  Resolved order items
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
     */
    private function ensureCouponMinimumOrder(?Coupon $coupon, array $items): void
    {
        if (! $coupon || ! $coupon->minimum_order) {
            return;
        }

        $subtotal = round(array_sum(array_column($items, 'subtotal')), 2);

        if ($subtotal < (float) $coupon->minimum_order) {
            throw new UnprocessableEntityHttpException(__('orders.coupon_minimum_order', ['amount' => number_format((float) $coupon->minimum_order, 2)]));
        }
    }

    /**
     * Resolve and prepare order items with row-level locking.
     *
     * Requested lines are first merged by product so that a cart repeating the
     * same product across multiple lines is validated and charged against a
     * single combined quantity. Without merging, each line would be stock
     * checked in isolation (allowing the total to exceed available stock) and
     * the bulk decrement would only apply the first matching CASE branch.
     *
     * Retrieves all requested products and locks their rows
     * using a "FOR UPDATE" query to prevent concurrent modifications.
     * This ensures stock consistency and prevents overselling when multiple
     * customers attempt to purchase the same product simultaneously.
     * Rows are locked in a deterministic order to avoid deadlocks between
     * concurrent orders whose carts overlap.
     *
     * @param array       $rawItems List of raw items (product_id, quantity)
     * @param StoreBranch $branch   Store branch associated with the order
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
     *
     * @return array Resolved and validated order items
     */
    private function resolveItems(array $rawItems, StoreBranch $branch): array
    {
        $quantities = $this->mergeQuantitiesByProduct($rawItems);

        $products = Product::select('id', 'name', 'store_id', 'status', 'quantity', 'price', 'sale_price')
            ->whereIn('id', array_keys($quantities))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $items = [];

        foreach ($quantities as $productId => $quantity) {
            $product = $products->get($productId) ?? throw new UnprocessableEntityHttpException(__('orders.product_not_found'));

            $this->validateProduct($product, $quantity, $branch);

            $unitPrice = (float) ($product->sale_price ?? $product->price);

            $items[] = [
                'product_id'   => $product->id,
                'product_name' => $product->name,
                'product'      => $product,
                'quantity'     => $quantity,
                'unit_price'   => $unitPrice,
                'subtotal'     => round($unitPrice * $quantity, 2),
            ];
        }

        return $items;
    }

    /**
     * Merge requested lines into a single total quantity per product.
     *
     * @param array $rawItems List of raw items (product_id, quantity)
     *
     * @return array<string, int> Map of product_id => combined quantity
     */
    private function mergeQuantitiesByProduct(array $rawItems): array
    {
        $quantities = [];

        foreach ($rawItems as $raw) {
            $productId = $raw['product_id'];

            $quantities[$productId] = ($quantities[$productId] ?? 0) + (int) $raw['quantity'];
        }

        return $quantities;
    }

    /**
     * Validate a single product against business rules.
     *
     * Extracted from resolveItems().
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
     */
    private function validateProduct(Product $product, int $quantity, StoreBranch $branch): void
    {
        if ($product->store_id !== $branch->store_id) {
            throw new UnprocessableEntityHttpException(__('orders.not_belongs_to_store', ['name' => $product->name]));
        }

        if ($product->status !== DefineStatus::ACTIVE) {
            throw new UnprocessableEntityHttpException(__('orders.unavailable', ['name' => $product->name]));
        }

        if ($product->quantity < $quantity) {
            throw new UnprocessableEntityHttpException(__('orders.not_enough_stock', ['name' => $product->name]));
        }
    }

    /**
     *
     * Persist the order and all related data to the database.
     * 
     * Responsibilities:
     * - Creating the order record with full snapshot data
     * - Inserting order items in bulk
     * - Decrementing product stock quantities
     * - Updating coupon usage count (if applicable)
     *
     * All operations are part of a single transaction managed by the caller.
     *
     * @param array        $data     Validated request data
     * @param StoreBranch  $branch   Store branch associated with the order
     * @param UserAddress  $address  Selected delivery address
     * @param Coupon|null  $coupon   Applied coupon (if any)
     * @param array        $items       Resolved order items
     * @param array        $pricing     Calculated pricing breakdown
     * @param string       $orderNumber Pre-allocated order number
     */
    private function persistOrder(array $data, StoreBranch $branch, UserAddress $address, ?Coupon $coupon, array $items, array $pricing, string $orderNumber): Order
    {
        $order = Order::create(
            $this->buildOrderAttributes($data, $branch, $address, $coupon, $pricing, $orderNumber)
        );

        $order->setRelation('storeBranch', $branch);
        $order->setRelation('store', $branch->store);

        $itemRows = $this->buildItemRows($order->id, $items);

        OrderItem::insert($itemRows);

        $this->stockService->decrement(array_column($items, 'quantity', 'product_id'));

        if ($coupon) {
            Coupon::whereKey($coupon->id)->update(['used_count' => DB::raw('used_count + 1')]);
        }

        $order->setRelation(
            'items',
            collect($itemRows)->map(fn ($row) => (new OrderItem())->forceFill($row))
        );

        return $order;
    }

    /**
     * Build the attributes array for inserting a new order record.
     *
     * Preparing a complete snapshot of the order, including:
     * - Pricing details (subtotal, delivery fee, discount, total)
     * - Commission data (rate and calculated amounts)
     * - Delivery address snapshot
     *
     * The delivery phone is resolved as follows:
     * - contact_phone: from the selected user address
     * - fallback: from the authenticated user's profile if not available
     *
     * @param array        $data     Validated request data
     * @param StoreBranch  $branch   Store branch associated with the order
     * @param UserAddress  $address  Selected delivery address
     * @param Coupon|null  $coupon   Applied coupon (if any)
     * @param array        $pricing     Calculated pricing breakdown
     * @param string       $orderNumber Pre-allocated order number
     *
     * @return array Attributes ready for order insertion
     */
    private function buildOrderAttributes(array $data, StoreBranch $branch, UserAddress $address, ?Coupon $coupon, array $pricing, string $orderNumber):array
    {
        return [
            'customer_id'           => auth()->id(),
            'store_id'              => $branch->store_id,
            'store_branch_id'       => $branch->id,
            'coupon_id'             => $coupon?->id,
            'order_number'          => $orderNumber,
            'notes'                 => $data['notes'] ?? null,
            'payment_method'        => $data['payment_method'],
            'order_status'          => OrderStatus::PENDING,
            'payment_status'        => PaymentStatus::PENDING,
            'subtotal'              => $pricing['subtotal'],
            'delivery_fee'          => $pricing['delivery_fee'],
            'discount'              => $pricing['discount'],
            'wallet_discount'       => $pricing['wallet_discount'],
            'total'                 => $pricing['total'],
            'commission_rate'       => $pricing['commission_rate'],
            'commission_amount'     => $pricing['commission_amount'],
            'vendor_earnings'       => $pricing['vendor_earnings'],
            'rider_earnings'        => $pricing['rider_earnings'],
            'delivery_address_line' => $address->address_line_1,
            'delivery_city'         => $address->city,
            'delivery_state'        => $address->state,
            'delivery_country'      => $address->country,
            'delivery_postal_code'  => $address->postal_code,
            'delivery_notes'        => $address->additional_info,
            'delivery_phone'        => $address->contact_phone ?? auth()->user()->phone,
            'delivery_latitude'     => $address->latitude,
            'delivery_longitude'    => $address->longitude,
        ];
    }

    /**
     * Build the payload for bulk inserting order items.
     *
     * Transforms the given items array into a structured format suitable for
     * database insertion. Each row represents a snapshot of the product at the
     * time of ordering (price, quantity, etc.).
     *
     * @param string $orderId The ID of the order being created
     * @param array  $items   List of items (product_id, quantity, unit_price, etc.)
     *
     * @return array Prepared rows for bulk insert into order_items table
    */
    private function buildItemRows(string $orderId, array $items): array
    {
        return array_map(fn ($item) => [
            'id'           => (string) Str::uuid(),
            'order_id'     => $orderId,
            'product_id'   => $item['product_id'],
            'product_name' => $item['product_name'],
            'quantity'     => $item['quantity'],
            'unit_price'   => $item['unit_price'],
            'subtotal'     => $item['subtotal'],
            'created_at'   => now(),
            'updated_at'   => now(),
        ], $items);
    }

    /**
     * Process payment for the order based on the selected payment method.
     *
     * Returns payment data when using online payment (VISA),
     * otherwise returns null for offline methods such as COD.
     *
     * @param Order $order
     *
     * @return array|null
     */
    private function handlePayment(Order $order): ?array
    {
        if ($order->payment_method !== PaymentMethod::VISA) {
            return null;
        }

        return $this->paymentService->createPaymentIntent($order);
    }

    /**
     * Calculate wallet discount if customer chose to use wallet balance.
     *
     * Returns 0 if use_wallet is false, the customer has no profile row, or the
     * balance is empty. The caller caps the returned amount against the order.
     */
    private function resolveWalletDiscount(array $data): array
    {
        if (empty($data['use_wallet'])) {
            return ['discount' => 0.0, 'profile' => null];
        }

        $profile = CustomerProfile::select('id', 'user_id', 'wallet_balance')
            ->where('user_id', auth()->id())
            ->lockForUpdate()
            ->first();

        if (! $profile || $profile->wallet_balance <= 0) {
            return ['discount' => 0.0, 'profile' => null];
        }

        return ['discount' => (float) $profile->wallet_balance, 'profile' => $profile];
    }

}