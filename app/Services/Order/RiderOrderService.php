<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Jobs\Order\FindRiderJob;
use App\Models\Order;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class RiderOrderService
{
    /**
     * Rider rejects the assigned order.
     *
     * Unassigns the rider and restarts the search automatically.
     * The order goes back to waiting for a rider and FindRiderJob fires again with a fresh 5 minute window.
     */
    public function rejectOrder(Order $order): Order
    {
        $this->validateRiderOwnership($order);
        $this->validateStatus($order, OrderStatus::RIDER_ASSIGNED, __('riders.cannot_reject'));

        $order->update([
            'rider_id'                  => null,
            'order_status'              => OrderStatus::WAITING_RIDER,
            'rider_assignment_attempts' => 0,
            'rider_search_started_at'   => now(),
        ]);

        FindRiderJob::dispatch($order);

        return $order;
    }

    /**
     * Rider confirms they have picked up the order from the branch.
     */
    public function pickupOrder(Order $order): Order
    {
        $this->validateRiderOwnership($order);
        $this->validateStatus($order, OrderStatus::RIDER_ASSIGNED, __('riders.cannot_pickup'));

        $order->update(['order_status' => OrderStatus::PICKED_UP]);

        return $order;
    }

    /**
     * Rider marks the order as delivered to the customer.
     *
     * Mark the order as delivered.
     * Payment status moves to paid since this is COD.
     */
    public function deliverOrder(Order $order): Order
    {
        $this->validateRiderOwnership($order);
        $this->validateStatus($order, OrderStatus::PICKED_UP, __('riders.cannot_deliver'));

        $order->update([
            'order_status'   => OrderStatus::DELIVERED,
            'payment_status' => PaymentStatus::PAID,
            'delivered_at'   => now(),
        ]);

        return $order;
    }

    /**
     * Verify the authenticated rider is the one assigned to this order.
     *
     * Prevents a rider from acting on another rider's order.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
     */
    private function validateRiderOwnership(Order $order): void
    {
        if ($order->rider_id !== auth()->id()) {
            throw new UnprocessableEntityHttpException(__('riders.not_belongs_to_you'));
        }
    }

    /**
     * Verify the order status for the order transitions.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
     */
    private function validateStatus(Order $order, OrderStatus $expected, string $message): void
    {
        if ($order->order_status !== $expected) {
            throw new UnprocessableEntityHttpException($message);
        }
    }
}