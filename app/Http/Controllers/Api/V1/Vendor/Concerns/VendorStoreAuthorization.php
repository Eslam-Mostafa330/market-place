<?php

namespace App\Http\Controllers\Api\V1\Vendor\Concerns;

use App\Models\Store;

trait VendorStoreAuthorization
{
    /**
     * Verifies that the given store belongs to the authenticated vendor.
     * Aborts with 404 if the store is owned by a different vendor because Vendors should not know other vendors’ stores exist.
     *
     * @param Store $store
     */
    protected function authorizeStore(Store $store): void
    {
        $user = auth()->user();

        abort_if(
            $store->vendor_profile_id !== $user->vendorProfile->id,
            404
        );
    }
}