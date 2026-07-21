<?php

namespace App\Events\Order;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;

class OrderCancelled
{
    use Dispatchable;

    /**
     * Raised once a cancellation and its compensation have been committed.
     *
     * The compensation itself (stock, coupon, wallet) stays inside the cancellation
     * transaction because it must be atomic with the status change. Only the effects
     * that cannot participate in that transaction — notifying the customer, reversing
     * the payment at the gateway — listen to this event.
     *
     * The cancellation details are carried as values because they describe the
     * cancellation that happened, not the order's current state.
     */
    public function __construct(
        public readonly string $orderId,
        public readonly string $customerId,
        public readonly string $orderNumber,
        public readonly ?string $cancelledBy,
        public readonly ?string $cancellationNote,
        public readonly ?string $paymentIntentId,
    ) {}

    /**
     * Build the event from an order that has just been cancelled.
     */
    public static function from(Order $order): self
    {
        return new self(
            orderId: $order->id,
            customerId: $order->customer_id,
            orderNumber: $order->order_number,
            cancelledBy: $order->cancelled_by,
            cancellationNote: $order->cancellation_note,
            paymentIntentId: $order->payment_intent_id,
        );
    }
}
