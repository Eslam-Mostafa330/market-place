<?php

namespace App\Http\Controllers\Api\V1\Rider\Concerns;

use App\Models\Order;

trait RiderOrderAuthorization
{
    /**
     * Ensure the authenticated rider has assigned to the order.
     * Prevents a rider from acting on another rider's order.
     * 
     * @param Store $store
     */
    protected function authorizeRiderOrder(Order $order): void
    {
        abort_if(
            $order->rider_id !== auth()->id(),
            404
        );
    }
}