<?php

namespace App\Events\Order;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;

class OrderStatusChanged
{
    use Dispatchable;

    /**
     * Dispatched when an order status changes.
     *
     * Captures the status at dispatch time so queued notifications report
     * the correct status, even if the order changes again.
     */
    public function __construct(
        public readonly string $orderId,
        public readonly string $customerId,
        public readonly string $orderNumber,
        public readonly OrderStatus $status,
        public readonly string $customerMessage,
    ) {}

    /**
     * Build the event from an order that has just been transitioned.
     */
    public static function from(Order $order, string $customerMessage): self
    {
        return new self(
            orderId: $order->id,
            customerId: $order->customer_id,
            orderNumber: $order->order_number,
            status: $order->order_status,
            customerMessage: $customerMessage,
        );
    }
}
