<?php

namespace App\Services\Order;

use App\Enums\DefineStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Jobs\CustomerPreference\RefreshCustomerPreferences;
use App\Models\Coupon;
use App\Models\CustomerProfile;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StoreBranch;
use App\Models\User;
use App\Models\UserAddress;
use App\Notifications\Order\NewOrderNotification;
use App\Services\LoyaltyService;
use App\Services\Payment\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class PlaceOrderService
{
    public function __construct(private readonly OrderPricingCalculatorService $calculator, private readonly PaymentService $paymentService, private readonly LoyaltyService $loyaltyService,) {}

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
        ['order' => $order, 'vendorUser' => $vendorUser] = DB::transaction(function () use ($data) {
            ['branch' => $branch, 'address' => $address, 'coupon' => $coupon] = $this->validateOrder($data);

            $items = $this->resolveItems($data['items'], $branch);

            ['discount' => $walletDiscount, 'profile' => $walletProfile] = $this->resolveWalletDiscount($data);

            $pricing = $this->calculator->calculate($items, $branch, (float) $branch->store->commission_rate, $coupon, $walletDiscount);

            ['order' => $order, 'vendorUser' => $vendorUser] = $this->persistOrder($data, $branch, $address, $coupon, $items, $pricing);

            if ($walletProfile && $pricing['wallet_discount'] > 0) {
                $this->loyaltyService->deductWalletBalance($walletProfile, $pricing['wallet_discount']);
            }

            return ['order' => $order, 'vendorUser' => $vendorUser];
        });

        RefreshCustomerPreferences::throttledDispatch($order->customer_id, 'order');

        $vendorUser?->notify(new NewOrderNotification(orderId: $order->id, orderNumber: $order->order_number, total: $order->total, itemsCount: $order->items->count(), branchName: $order->storeBranch->name, storeName: $order->store->name));

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
        $coupon = Coupon::select('id', 'store_id', 'status', 'starts_at', 'expires_at', 'usage_limit_per_user', 'coupon_type', 'value')
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
            $usedByCustomer = Order::where('customer_id', auth()->id())->where('coupon_id', $coupon->id)->count();

            if ($usedByCustomer >= $coupon->usage_limit_per_user) {
                throw new UnprocessableEntityHttpException(__('orders.coupon_usage_reached'));
            }
        }

        return $coupon;
    }

    /**
     * Resolve and prepare order items with row-level locking.
     *
     * Retrieves all requested products and locks their rows
     * using a "FOR UPDATE" query to prevent concurrent modifications.
     * This ensures stock consistency and prevents overselling when multiple
     * customers attempt to purchase the same product simultaneously.
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
        $products = Product::select('id', 'name', 'store_id', 'status', 'quantity', 'price', 'sale_price')
            ->whereIn('id', array_column($rawItems, 'product_id'))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        return array_map(function ($raw) use ($products, $branch) {
            $product = $products->get($raw['product_id']) ?? throw new UnprocessableEntityHttpException('One or more products were not found.');

            $this->validateProduct($product, $raw['quantity'], $branch);

            $unitPrice = (float) ($product->sale_price ?? $product->price);

            return [
                'product_id'   => $product->id,
                'product_name' => $product->name,
                'product'      => $product,
                'quantity'     => $raw['quantity'],
                'unit_price'   => $unitPrice,
                'subtotal'     => round($unitPrice * $raw['quantity'], 2),
            ];
        }, $rawItems);
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
     * @param array        $items    Resolved order items
     * @param array        $pricing  Calculated pricing breakdown
     * 
     * @return array{order: Order, vendorUser: User|null}
     */
    private function persistOrder(array $data, StoreBranch $branch, UserAddress $address, ?Coupon $coupon, array $items, array $pricing): array
    {
        $order = Order::create(
            $this->buildOrderAttributes($data, $branch, $address, $coupon, $pricing)
        );

        $order->setRelation('storeBranch', $branch);
        $order->setRelation('store', $branch->store);

        $itemRows = $this->buildItemRows($order->id, $items);

        OrderItem::insert($itemRows);

        $this->decrementStock($items);

        if ($coupon) {
            Coupon::whereKey($coupon->id)->update(['used_count' => DB::raw('used_count + 1')]);
        }

        $order->setRelation(
            'items',
            collect($itemRows)->map(fn ($row) => (new OrderItem())->forceFill($row))
        );

        $vendorUser = User::select('users.id')
            ->join('vendor_profiles', 'vendor_profiles.user_id', '=', 'users.id')
            ->where('vendor_profiles.id', $branch->store->vendor_profile_id)
            ->first();

        return ['order' => $order, 'vendorUser' => $vendorUser];
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
     * @param array        $pricing  Calculated pricing breakdown
     *
     * @return array Attributes ready for order insertion
     */
    private function buildOrderAttributes(array $data, StoreBranch $branch, UserAddress $address, ?Coupon $coupon, array $pricing):array
    {
        return [
            'customer_id'           => auth()->id(),
            'store_id'              => $branch->store_id,
            'store_branch_id'       => $branch->id,
            'coupon_id'             => $coupon?->id,
            'order_number'          => $this->generateOrderNumber(),
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
     * Decrements stock quantities for multiple products.
     *
     * This method performs a bulk update using a SQL CASE statement, allowing each
     * product to be updated with a different decrement value in a single query.
     *
     * @param array $items
     * @return void
    */
    private function decrementStock(array $items): void
    {
        $ids = collect($items)->pluck('product_id');

        $cases = collect($items)
            ->map(fn ($item) => "WHEN id = '{$item['product_id']}' THEN quantity - {$item['quantity']}")
            ->implode(' ');

        Product::whereIn('id', $ids)->update(['quantity' => DB::raw("CASE {$cases} END")]);
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
     * Returns 0 if use_wallet is false or customer has no balance.
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

        return ['discount' => (float) $profile->wallet_balance, 'profile' => $profile];
    }

    /**
     * Generate a daily order number based on the current date and order count.
     *
     * Format: ORD-YYYYMMDD-00001
     *
     * The sequence is derived by counting today's existing orders and adding one.
     * This provides a simple incremental number per day, but it is not a true
     * database sequence and may be prone to race conditions under high concurrency
     * unless used within a transaction with proper locking.
     *
     * @return string Generated order number
     */
    private function generateOrderNumber(): string
    {
        $today = today()->toDateString();

        $sequence = Order::whereDate('created_at', $today)
            ->lockForUpdate()
            ->count() + 1;

        return 'ORD-' . now()->format('Ymd') . '-' . str_pad($sequence, 5, '0', STR_PAD_LEFT);
    }
}