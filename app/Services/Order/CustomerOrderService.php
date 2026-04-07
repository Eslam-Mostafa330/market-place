<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Notifications\Order\OrderCancelledNotification;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class CustomerOrderService
{
    /**
     * Customer cancels their own order.
     *
     * Allowed from any status before delivered.
     * Ensure the order is in a cancellable status before allowing cancellation.
     * Notify the customer about the cancellation and reason for better transparency and communication.
     */
    public function cancelOrder(Order $order, int $reason, ?string $note = null): Order
    {
        $this->validateCancellable($order);

        $order->update([
            'order_status'        => OrderStatus::CANCELLED,
            'cancelled_by'        => 'customer',
            'cancellation_reason' => $reason,
            'cancellation_note'   => $note,
        ]);

        $order->customer->notify(new OrderCancelledNotification($order));

        return $order;
    }

    /**
     * Verify the order is in a cancellable status.
     *
     * Cannot cancel what is already delivered or already cancelled.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
     */
    private function validateCancellable(Order $order): void
    {
        $nonCancellableStatuses = OrderStatus::nonCancellableStatuses();

        if (in_array($order->order_status, $nonCancellableStatuses)) {
            throw new UnprocessableEntityHttpException(__('orders.cannot_cancel'));
        }
    }
}