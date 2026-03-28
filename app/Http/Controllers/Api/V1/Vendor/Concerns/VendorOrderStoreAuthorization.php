<?php

namespace App\Http\Controllers\Api\V1\Vendor\Concerns;

use App\Models\Order;

trait VendorOrderStoreAuthorization
{
    /**
     * Verifies that the given order belongs to the authenticated vendor.
     * Aborts with 404 if the order is owned by a different vendor because Vendors should not know other vendors’ orders exist.
     *
     * @param Order $order
     */
    protected function authorizeOrder(Order $order): void
    {
        $vendorStoreId = auth()->user()->store?->id;

        abort_if(
            ! $vendorStoreId || $order->store_id !== $vendorStoreId,
            404
        );
    }
}