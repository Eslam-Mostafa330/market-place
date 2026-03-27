<?php

namespace App\Services\Order;

use App\Enums\DefineStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StoreBranch;
use App\Models\UserAddress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlaceOrderService
{
    public function __construct(private readonly OrderPricingCalculatorService $calculator) {}

    /** #### Step one (entry point) ####
     * Run the full order placement pipeline.
     *
     * Throws InvalidArgumentException on any business rule failure.
     *
     * @param  array  $data  Validated data from PlaceOrderRequest
     * @return Order         The newly created order with items relation loaded
     */
    public function handle(array $data): Order
    {
        ['branch' => $branch, 'address' => $address, 'coupon' => $coupon] = $this->validateOrder($data);

        $items = $this->resolveItems($data['items'], $branch);

        $pricing = $this->calculator->calculate($items, $branch, (float) $branch->store->commission_rate, $coupon);

        return $this->createOrder($data, $branch, $address, $coupon, $items, $pricing);
    }

    /** #### Step one (business rule validation) ####
     * Validate branch, store, address, and coupon.
     * Returns resolved models so downstream steps
     *
     * @throws \InvalidArgumentException
     */
    private function validateOrder(array $data): array
    {
        $branch = StoreBranch::with('store')->findOrFail($data['store_branch_id']);

        if ($branch->status !== DefineStatus::ACTIVE) {
            throw new \InvalidArgumentException(__('branches.branch_unavailable'));
        }

        $address = UserAddress::findOrFail($data['address_id']);

        if ($address->user_id !== auth()->id()) {
            throw new \InvalidArgumentException(__('addresses.address_not_belong_to_user'));
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
     * @throws \InvalidArgumentException
     */
    private function validateCoupon(string $code, StoreBranch $branch): Coupon
    {
        $coupon = Coupon::where('code', $code)->firstOrFail();

        if ($coupon->status !== DefineStatus::ACTIVE) {
            throw new \InvalidArgumentException(__('coupons.coupon_inactive'));
        }

        if ($coupon->store_id !== null && $coupon->store_id !== $branch->store_id) {
            throw new \InvalidArgumentException(__('coupons.coupon_not_valid_per_store'));
        }

        if ($coupon->starts_at && now()->lt($coupon->starts_at)) {
            throw new \InvalidArgumentException(__('coupons.coupon_not_active_yet'));
        }

        if ($coupon->expires_at && now()->gt($coupon->expires_at)) {
            throw new \InvalidArgumentException(__('coupons.coupon_expired'));
        }

        if ($coupon->usage_limit_per_user) {
            $usedByCustomer = Order::where('customer_id', auth()->id())->where('coupon_id', $coupon->id)->count();

            if ($usedByCustomer >= $coupon->usage_limit_per_user) {
                throw new \InvalidArgumentException(__('coupons.coupon_usage_reached'));
            }
        }

        return $coupon;
    }

    /** #### Step two (resolve items) ####
     * Load all products, validate each, and build item rows.
     *
     * Locks the price at order time — if the vendor changes the price
     * anytime, this order still reflects what the customer paid.
     *
     * @throws \InvalidArgumentException
     */
    private function resolveItems(array $rawItems, StoreBranch $branch): array
    {
        $products = Product::whereIn('id', array_column($rawItems, 'product_id'))->get()->keyBy('id');

        return array_map(function ($raw) use ($products, $branch) {
            $product = $products->get($raw['product_id']) ?? throw new \InvalidArgumentException('One or more products were not found.');

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
     * @throws \InvalidArgumentException
     */
    private function validateProduct(Product $product, int $quantity, StoreBranch $branch): void
    {
        if ($product->store_id !== $branch->store_id) {
            throw new \InvalidArgumentException(__('products.not_belongs_to_store', ['name' => $product->name]));
        }

        if ($product->status !== DefineStatus::ACTIVE) {
            throw new \InvalidArgumentException(__('products.unavailable', ['name' => $product->name]));
        }

        if ($product->quantity < $quantity) {
            throw new \InvalidArgumentException(__('products.not_enough_stock', ['name' => $product->name]));
        }
    }

    /** #### Step three (the calculations that was handled by OrderPricingCalculatorService) ####
     *  #### Step four (persist order) ####
     * Persist the order and all related data within a database transaction.
     * 
     * Responsibilities:
     * - Insert the order record with full snapshot data
     * - Insert associated order items (bulk operation)
     * - Decrement product stock quantities
     * - Decrement coupon usage count (if applicable)
     * 
     *  Ensures data consistency by executing all operations atomically.
     *
     * @param array        $data     Validated request data
     * @param StoreBranch  $branch   Store branch associated with the order
     * @param UserAddress  $address  Selected delivery address
     * @param Coupon|null  $coupon   Applied coupon (if any)
     * @param array        $items    Prepared order items
     * @param array        $pricing  Calculated pricing breakdown
     *
     * @return Order The newly created order instance
     */
    private function createOrder(array $data, StoreBranch $branch, UserAddress $address, ?Coupon $coupon, array $items, array $pricing): Order
    {
        return DB::transaction(function () use ($data, $branch, $address, $coupon, $items, $pricing) {

            $order = Order::create($this->buildOrderAttributes($data, $branch, $address, $coupon, $pricing));

            $itemRows = $this->buildItemRows($order->id, $items);

            OrderItem::insert($itemRows);

            $this->decrementStock($items);

            if ($coupon) {
                $coupon->increment('used_count');
            }

            $order->setRelation('items', collect($itemRows)->map(fn ($row) => (new OrderItem())->forceFill($row)));

            return $order;
        });
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
     * Extracted from createOrder() to keep the transaction logic clean and readable.
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
            'delivery_phone'        => $address->phone ?? auth()->user()->phone,
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
     * Decrements stock quantities for multiple products in a single database query.
     *
     * This method performs a bulk update using a SQL CASE statement, allowing each
     * product to be updated with a different decrement value in one query instead
     * of executing multiple queries (one per product).
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
     * Generate a unique sequential order number for the current day.
     *
     * Format: ORD-YYYYMMDD-00001
     *
     * The sequence resets daily and increments per order. This method is designed
     * to be executed within a database transaction using row-level locking
     * (lockForUpdate) to prevent duplicate sequence numbers under concurrent requests.
     *
     * @return string Generated order number
     */
    private function generateOrderNumber(): string
    {
        $date      = now()->format('Ymd');
        $lastOrder = Order::whereDate('created_at', today())->lockForUpdate()->latest('created_at')->first();

        $sequence = $lastOrder
            ? ((int) substr($lastOrder->order_number, -5)) + 1
            : 1;

        return 'ORD-' . $date . '-' . str_pad($sequence, 5, '0', STR_PAD_LEFT);
    }
}