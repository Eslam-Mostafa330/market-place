<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\Order\OrderStatusChanged;
use App\Jobs\Order\FindRiderJob;
use App\Models\Order;
use App\Services\Customer\LoyaltyService;
use App\Services\Payment\RiderPayoutService;
use App\Services\Payment\VendorPayoutService;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class RiderOrderService
{
    public function __construct(private readonly RiderPayoutService  $riderPayoutService, private readonly VendorPayoutService $vendorPayoutService, private readonly LoyaltyService $loyaltyService) {}

    /**
     * Rider rejects the assigned order.
     *
     * Unassigns the rider and restarts the search automatically.
     * The order goes back to waiting for a rider and FindRiderJob fires again with a fresh 5 minute window.
     */
    public function rejectOrder(string $orderId, string $riderId): Order
    {
        $order = DB::transaction(function () use ($orderId, $riderId) {
            $order = $this->lockRiderOrder($orderId, $riderId, ['id', 'rider_id', 'order_status', 'order_number']);

            $this->validateStatus($order, OrderStatus::RIDER_ASSIGNED, __('riders.cannot_reject'));

            $order->update([
                'rider_id'                  => null,
                'order_status'              => OrderStatus::WAITING_RIDER,
                'rider_assignment_attempts' => 0,
                'rider_search_started_at'   => now(),
            ]);

            return $order;
        });

        FindRiderJob::dispatchFor($order->id);

        return $order;
    }

    /**
     * Rider confirms they have picked up the order from the branch.
     * Validates that the order is in the correct status for pickup before allowing the status change.
     * Notifies the customer that their order is on the way after pickup.
     */
    public function pickupOrder(string $orderId, string $riderId): Order
    {
        $order = DB::transaction(function () use ($orderId, $riderId) {
            $order = $this->lockRiderOrder($orderId, $riderId, ['id', 'order_number', 'order_status', 'customer_id']);

            $this->validateStatus($order, OrderStatus::RIDER_ASSIGNED, __('riders.cannot_pickup'));

            $order->update(['order_status' => OrderStatus::PICKED_UP]);

            return $order;
        });

        $this->announceStatusChange($order, __('notifications.order_picked_up'));

        return $order;
    }

    /**
     * Mark an order as delivered.
     *
     * Uses a row lock to prevent concurrent deliveries from awarding
     * loyalty points more than once.
     */
    public function deliverOrder(string $orderId, string $riderId): Order
    {
        $order = DB::transaction(function () use ($orderId, $riderId) {
            $order = $this->lockRiderOrder(
                $orderId,
                $riderId,
                ['id', 'rider_id', 'store_id', 'order_number', 'order_status', 'customer_id', 'payment_method', 'discount', 'subtotal', 'wallet_discount', 'vendor_earnings', 'rider_earnings']
            );

            $this->validateStatus($order, OrderStatus::PICKED_UP, __('riders.cannot_deliver'));

            $order->load('store.vendorProfile:id,user_id');

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

        $this->announceStatusChange($order, __('notifications.order_delivered'));

        return $order;
    }

    /**
     * Load and lock one of this rider's orders.
     *
     * The lock is what makes the subsequent status check safe against a concurrent
     * request for the same order; it is held until the surrounding transaction commits.
     */
    private function lockRiderOrder(string $orderId, string $riderId, array $columns): Order
    {
        return Order::select($columns)
            ->where('id', $orderId)
            ->where('rider_id', $riderId)
            ->lockForUpdate()
            ->firstOrFail();
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
    private function validateStatus(Order $order, OrderStatus $expected, string $message): void
    {
        if ($order->order_status !== $expected) {
            throw new UnprocessableEntityHttpException($message);
        }
    }
}
