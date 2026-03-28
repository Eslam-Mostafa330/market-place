<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Models\Order;

class VendorOrderService
{
    /**
     * Accept a pending order.
     *
     * Validates that The order is still in pending status
     *
     * @throws \InvalidArgumentException
     */
    public function acceptOrder(Order $order): Order
    {
        $this->validateStatus($order);

        $order->update(['order_status' => OrderStatus::ACCEPTED]);

        return $order;
    }

    /**
     * Verify the order is in a status that can be accepted.
     *
     * Only PENDING orders can be accepted — anything else means
     * the order has already moved forward or been cancelled.
     *
     * @throws \InvalidArgumentException
     */
    private function validateStatus(Order $order): void
    {
        if ($order->order_status !== OrderStatus::PENDING) {
            throw new \InvalidArgumentException(__('vendors.accept_only_pending_orders'));
        }
    }
}