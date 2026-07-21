<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Events\Order\OrderStatusChanged;
use App\Models\Order;
use App\Jobs\Order\FindRiderJob;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class VendorOrderService
{
    /**
     * Accept a pending order.
     *
     * Validates that the order is still in pending status, then moves it to accepted.
     * Notifies the customer that their order has been accepted and is being prepared.
     */
    public function acceptOrder(string $orderId): Order
    {
        $order = $this->transition($orderId, OrderStatus::PENDING, __('vendors.ensure_pending_orders'), [
            'order_status' => OrderStatus::ACCEPTED,
        ]);

        $this->announceStatusChange($order, __('notifications.order_accepted'));

        return $order;
    }

    /**
     * Prepare the accepted order.
     *
     * Validates that the order is still in accepted status, then moves it to preparing.
     */
    public function prepareOrder(string $orderId): Order
    {
        $order = $this->transition($orderId, OrderStatus::ACCEPTED, __('vendors.ensure_accepted_orders'), [
            'order_status' => OrderStatus::PREPARING,
        ]);

        $this->announceStatusChange($order, __('notifications.order_preparing'));

        return $order;
    }

    /**
     * Mark the order as ready for pickup.
     *
     * Validates that the order is still in preparing status, then moves it to waiting
     * rider and stamps the rider search start time. This is the trigger point for the
     * rider search job, which is dispatched only after the transition has committed.
     */
    public function markReady(string $orderId): Order
    {
        $order = $this->transition($orderId, OrderStatus::PREPARING, __('vendors.ensure_preparing_orders'), [
            'order_status'            => OrderStatus::WAITING_RIDER,
            'rider_search_started_at' => now(),
        ]);

        FindRiderJob::dispatchFor($order->id);

        return $order;
    }

    /**
     * Apply a guarded status transition to one of this vendor's orders.
     *
     * The order row is locked before its status is checked, so two concurrent
     * requests cannot both read the old status and both apply the transition.
     * Without the lock this is a read-then-write race that silently allows an
     * order to advance twice.
     *
     * The update goes through the model rather than the query builder so the
     * order's activity log still records the status change.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
     */
    private function transition(string $orderId, OrderStatus $expected, string $message, array $attributes): Order
    {
        $storeId = auth()->user()->store?->id;

        return DB::transaction(function () use ($orderId, $storeId, $expected, $message, $attributes) {
            $order = Order::select(['id', 'order_number', 'order_status', 'customer_id'])
                ->where('id', $orderId)
                ->where('store_id', $storeId)
                ->lockForUpdate()
                ->firstOrFail();

            $this->validateOrderStatus($order, $expected, $message);

            $order->update($attributes);

            return $order;
        });
    }

    /**
     * Announce the committed status change; the customer notification listens.
     */
    private function announceStatusChange(Order $order, string $message): void
    {
        event(OrderStatusChanged::from($order, $message));
    }

    /**
     * Verify the order status for the order transitions.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
     */
    private function validateOrderStatus(Order $order, OrderStatus $expected, string $message): void
    {
        if ($order->order_status !== $expected) {
            throw new UnprocessableEntityHttpException($message);
        }
    }
}
