<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Jobs\Order\FindRiderJob;
use App\Models\Order;
use App\Models\User;
use App\Notifications\Order\OrderStatusUpdatedNotification;
use App\Services\LoyaltyService;
use App\Services\Payment\RiderPayoutService;
use App\Services\Payment\VendorPayoutService;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class RiderOrderService
{
    public function __construct(
        private readonly RiderPayoutService  $riderPayoutService,
        private readonly VendorPayoutService $vendorPayoutService,
        private readonly LoyaltyService      $loyaltyService,
    ) {}

    /**
     * Rider rejects the assigned order.
     *
     * Unassigns the rider and restarts the search automatically.
     * The order goes back to waiting for a rider and FindRiderJob fires again with a fresh 5 minute window.
     */
    public function rejectOrder(Order $order): Order
    {
        $this->validateStatus($order, OrderStatus::RIDER_ASSIGNED, __('riders.cannot_reject'));

        $order->update([
            'rider_id'                  => null,
            'order_status'              => OrderStatus::WAITING_RIDER,
            'rider_assignment_attempts' => 0,
            'rider_search_started_at'   => now(),
        ]);

        FindRiderJob::dispatch($order->id);

        return $order;
    }

    /**
     * Rider confirms they have picked up the order from the branch.
     * Validates that the order is in the correct status for pickup before allowing the status change.
     * Notifies the customer that their order is on the way after pickup.
     */
    public function pickupOrder(Order $order): Order
    {
        $this->validateStatus($order, OrderStatus::RIDER_ASSIGNED, __('riders.cannot_pickup'));

        $order->update(['order_status' => OrderStatus::PICKED_UP]);

        User::query()->select('id')->find($order->customer_id)?->notify(new OrderStatusUpdatedNotification($order->id, $order->order_number, $order->order_status, __('notifications.order_picked_up')));

        return $order;
    }

    /**
     * Rider marks the order as delivered to the customer.
     *
     * Mark the order as delivered.
     * Validates that the order is in the correct status for delivery.
     * Create payout record for VISA orders automatically for riders and vendors.
     * Notifies the customer that their order has been delivered after delivery.
     */
    public function deliverOrder(Order $order): Order
    {
        $order = DB::transaction(function () use ($order) {
            $this->validateStatus($order, OrderStatus::PICKED_UP, __('riders.cannot_deliver'));

            $order->update([
                'order_status'      => OrderStatus::DELIVERED,
                'delivered_at'      => now(),
                ...($order->payment_method === PaymentMethod::CASH
                ? ['payment_status' => PaymentStatus::PAID]
                : []),
            ]);

            $this->riderPayoutService->createPayoutIfNeeded($order);
            $this->vendorPayoutService->createPayoutIfNeeded($order);
            $this->loyaltyService->awardPoints($order);
            
            return $order;
        });

        User::query()->select('id')->find($order->customer_id)?->notify(new OrderStatusUpdatedNotification($order->id, $order->order_number, $order->order_status, __('notifications.order_delivered')));

        return $order;
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