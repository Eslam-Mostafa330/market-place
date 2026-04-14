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
     * Ensure the order belongs to the authenticated customer.
     * Notify the customer about the cancellation and reason for better transparency and communication.
     */
    public function cancelOrder(string $orderId, int $reason, ?string $note = null, string $customerId): Order
    {
        $order = Order::select(['id', 'order_number', 'order_status', 'customer_id', 'cancelled_by'])
            ->where('id', $orderId)
            ->where('customer_id', $customerId)
            ->firstOrFail();

        $this->validateCancellable($order);

        $order->update([
            'order_status'        => OrderStatus::CANCELLED,
            'cancelled_by'        => 'customer',
            'cancellation_reason' => $reason,
            'cancellation_note'   => $note,
        ]);

        auth()->user()?->notify(new OrderCancelledNotification(orderId: $order->id, orderNumber: $order->order_number, cancelledBy: $order->cancelled_by, cancellationNote: $order->cancellation_note));

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