<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Enums\CancellationReason;
use App\Jobs\Order\FindRiderJob;
use App\Models\Order;
use App\Models\User;
use App\Notifications\Order\OrderCancelledNotification;
use App\Notifications\Order\RiderAssignedNotification;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class AdminOrderService
{
    /**
     * Manually assign a specific rider to an order.
     *
     * Admin picks the rider manually.
     * Ensure the order is still waiting for a rider before allowing manual assignment.
     */
    public function assignRider(Order $order, User $rider): Order
    {
        $this->ensureOrderStatus($order, [OrderStatus::WAITING_RIDER], __('orders.not_waiting_status'));

        $this->ensureAvailableRider($rider);

        $order->update([
            'rider_id'     => $rider->id,
            'order_status' => OrderStatus::RIDER_ASSIGNED,
        ]);

        $rider->notify(new RiderAssignedNotification($order, $order->storeBranch->slug));

        return $order;
    }

    /**
     * Cancel an order.
     *
     * Admin can cancel orders that are not already cancelled or delivered.
     * Ensure the order is not in a not canceled or delivered status before allowing cancellation.
     * Notify the customer about the cancellation with the reason and note for better transparency and communication.
     */
    public function cancelOrder(Order $order, ?string $note = null): Order
    {
        $nonCancellableStatuses = OrderStatus::nonCancellableStatuses();

        $this->ensureOrderStatus($order, $nonCancellableStatuses, __('orders.cannot_cancel'), false);

        $order->update([
            'order_status'        => OrderStatus::CANCELLED,
            'cancelled_by'        => 'admin',
            'cancellation_reason' => CancellationReason::OTHER,
            'cancellation_note'   => $note,
        ]);

        $order->customer->notify(new OrderCancelledNotification($order));

        return $order;
    }

    /**
     * Extend the rider search for another 5 minutes.
     *
     * Resets the attempts counter and re-dispatches FindRiderJob.
     * Ensures the order is still waiting for a rider before allowing extension.
     * Admin manually triggers this when escalation notification is received.
     */
    public function extendSearch(Order $order): Order
    {
        $this->ensureOrderStatus($order, [OrderStatus::WAITING_RIDER], __('orders.not_waiting_status'));

        $order->update([
            'rider_assignment_attempts' => 0,
            'rider_search_started_at'   => now(),
        ]);

        FindRiderJob::dispatch($order);

        return $order;
    }

    /**
     * Ensure the order status matches (or does not match) a given set of statuses.
     *
     * @param  bool  $shouldBeIn  If true → status must be in the array.
     *                            If false → status must NOT be in the array.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
     */
    private function ensureOrderStatus(Order $order, array $statuses, string $message, bool $shouldBeIn = true): void
    {
        $inArray = in_array($order->order_status, $statuses);

        if ($shouldBeIn ? ! $inArray : $inArray) {
            throw new UnprocessableEntityHttpException($message);
        }
    }

    /**
     * Ensure the given rider is available.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
     */
    private function ensureAvailableRider(User $rider): void
    {
        if (! $rider->isAvailableRider()) {
            throw new UnprocessableEntityHttpException(__('riders.rider_not_available'));
        }
    }
}