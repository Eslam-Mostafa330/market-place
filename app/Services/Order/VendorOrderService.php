<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Jobs\Order\FindRiderJob;
use App\Models\User;
use App\Notifications\Order\OrderStatusUpdatedNotification;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class VendorOrderService
{
    /**
     * Accept a pending order.
     *
     * Validates that The order is still in pending status
     * Then moves the order to accepted status.
     * Notifies the customer that their order has been accepted and is being prepared.
     */
    public function acceptOrder(Order $order): Order
    {
        $this->validateOrderStatus($order, OrderStatus::PENDING, __('vendors.ensure_pending_orders'));

        $order->update(['order_status' => OrderStatus::ACCEPTED]);

        User::query()->select('id')->find($order->customer_id)?->notify(new OrderStatusUpdatedNotification($order->id, $order->order_number, $order->order_status, __('notifications.order_accepted')));

        return $order;
    }

    /**
     * Prepare the accepted order.
     * 
     * Validates that The order is still in accepted status
     * Then Moves order from accepted to preparing.
     */
    public function prepareOrder(Order $order): Order
    {
        $this->validateOrderStatus($order, OrderStatus::ACCEPTED, __('vendors.ensure_accepted_orders'));

        $order->update(['order_status' => OrderStatus::PREPARING]);

        return $order;
    }

    /**
     * Mark the order as ready for pickup.
     *
     * Validates that The order is still in preparing status
     * Then moves the order to waiting rider status and sets the rider search start time.
     * The trigger point for the rider search job.
     */
    public function markReady(Order $order): Order
    {
        $this->validateOrderStatus($order, OrderStatus::PREPARING, __('vendors.ensure_preparing_orders'));

        $order->update([
            'order_status' => OrderStatus::WAITING_RIDER,
            'rider_search_started_at' => now(),
        ]);

        FindRiderJob::dispatch($order);

        return $order;
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