<?php

namespace App\Http\Controllers\Api\V1\Customer\Concerns;

use App\Models\Order;

trait CustomerOrderAuthorization
{
    /**
     * Verifies that the given order belongs to the authenticated customer.
     * Aborts with 404 if the order is owned by a different customer because Customers should not know other customers’ orders exist.
     *
     * @param Order $order
     */
    protected function authorizeOrder(Order $order): void
    {
        $customerId = auth()->user()->id;

        abort_if(
            ! $customerId || $order->customer_id !== $customerId,
            404
        );
    }
}