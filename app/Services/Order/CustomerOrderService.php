<?php

namespace App\Services\Order;

use App\Enums\CancellationReason;
use App\Models\Order;

class CustomerOrderService
{
    public function __construct(private readonly CancelOrderService $cancelOrderService) {}

    /**
     * Customer cancels their own order.
     *
     * Allowed from any status before delivered. Scoping by customer id ensures a
     * customer can only cancel their own order.
     *
     * Compensation (stock, coupon, wallet, gateway) and the customer notification
     * are owned by CancelOrderService so the customer and admin paths can never
     * drift apart on what cancelling actually reverses.
     */
    public function cancelOrder(string $orderId, CancellationReason $reason, string $customerId, ?string $note = null): Order
    {
        return $this->cancelOrderService->cancel(
            orderId: $orderId,
            reason: $reason,
            note: $note,
            cancelledBy: 'customer',
            customerId: $customerId,
        );
    }
}
