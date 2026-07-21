<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Enums\CancellationReason;
use App\Jobs\Order\FindRiderJob;
use App\Models\Order;
use App\Models\User;
use App\Notifications\Order\RiderAssignedNotification;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class AdminOrderService
{
    public function __construct(private readonly CancelOrderService $cancelOrderService) {}

    /**
     * Manually assign a specific rider to an order.
     *
     * Admin picks the rider manually.
     * Ensure the order is still waiting for a rider before allowing manual assignment.
     * Set a manual relation to display rider's minimal info
     */
    public function assignRider(string $orderId, string $riderId): Order
    {
        $order = Order::select(['id', 'order_status', 'store_branch_id'])
            ->with('storeBranch:id,slug')
            ->findOrFail($orderId);

        $this->ensureOrderStatus($order, [OrderStatus::WAITING_RIDER], __('orders.not_waiting_status'));

        $rider = User::select(['id', 'role', 'name', 'phone'])
            ->with('riderProfile:id,user_id,rider_availability')
            ->findOrFail($riderId);

        $this->ensureAvailableRider($rider);

        $order->update([
            'rider_id'     => $riderId,
            'order_status' => OrderStatus::RIDER_ASSIGNED,
        ]);

        $order->setRelation('rider', $rider);

        $rider->notify(new RiderAssignedNotification(orderId: $order->id, branchSlug: $order->storeBranch->slug));

        return $order;
    }

    /**
     * Cancel an order.
     *
     * Admin can cancel orders that are not already cancelled or delivered.
     *
     * Compensation (stock, coupon, wallet, gateway) and the customer notification
     * are owned by CancelOrderService so the admin and customer paths can never
     * drift apart on what cancelling actually reverses.
     */
    public function cancelOrder(string $orderId, ?string $note = null): Order
    {
        return $this->cancelOrderService->cancel(
            orderId: $orderId,
            reason: CancellationReason::OTHER,
            note: $note,
            cancelledBy: 'admin',
        );
    }

    /**
     * Extend the rider search for another 5 minutes.
     *
     * Resets the attempts counter and re-dispatches FindRiderJob.
     * Ensures the order is still waiting for a rider before allowing extension.
     * Admin manually triggers this when escalation notification is received.
     */
    public function extendSearch(string $orderId): Order
    {
        $order = Order::select(['id', 'order_status', 'rider_assignment_attempts', 'rider_search_started_at'])
            ->findOrFail($orderId);

        $this->ensureOrderStatus($order, [OrderStatus::WAITING_RIDER], __('orders.not_waiting_status'));

        $order->update([
            'rider_assignment_attempts' => 0,
            'rider_search_started_at'   => now(),
        ]);

        FindRiderJob::dispatchFor($order->id);

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