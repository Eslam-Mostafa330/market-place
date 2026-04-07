<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Models\Order;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class CustomerOrderService
{
    /**
     * Customer cancels their own order.
     *
     * Allowed from any status before DELIVERED.
     * Once delivered the order is final — no cancellation possible.
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