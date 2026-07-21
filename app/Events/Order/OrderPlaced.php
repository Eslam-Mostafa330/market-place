<?php

namespace App\Events\Order;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;

class OrderPlaced
{
    use Dispatchable;

    /**
     * Dispatched after an order is placed.
     *
     * Carries identifiers instead of the model so queued listeners fetch
     * the latest data explicitly.
     */
    public function __construct(
        public readonly string $orderId,
        public readonly string $customerId,
        public readonly string $storeId,
    ) {}

    /**
     * Build the event from a freshly placed order.
     */
    public static function from(Order $order): self
    {
        return new self(
            orderId: $order->id,
            customerId: $order->customer_id,
            storeId: $order->store_id,
        );
    }
}
