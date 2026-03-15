<?php

namespace App\Http\Controllers\Api\V1\Vendor\Concerns;

use App\Models\Store;
use App\Models\StoreBranch;
use App\Models\VendorProfile;

trait VendorStoreAuthorization
{
    /**
     * Retrieves the authenticated vendor's profile.
     * Used to scope all store and branch operations to the vendor's own resources only.
     *
     * @return VendorProfile
     */
    protected function vendorProfile(): VendorProfile
    {
        return VendorProfile::where('user_id', auth()->id())->firstOrFail();
    }

    /**
     * Verifies that the given store belongs to the authenticated vendor.
     * Aborts with 403 if the store is owned by a different vendor.
     *
     * @param Store $store
     */
    protected function authorizeStore(Store $store): void
    {
        abort_if($store->vendor_profile_id !== $this->vendorProfile()->id, 403);
    }

    /**
     * Verifies the full ownership chain: branch → store → vendor profile.
     * Aborts with 404 if the branch does not belong to the given store,
     * and with 403 if the store does not belong to the authenticated vendor.
     *
     * @param Store       $store
     * @param StoreBranch $branch
     */
    protected function authorizeBranch(Store $store, StoreBranch $branch): void
    {
        $this->authorizeStore($store);
        abort_if($branch->store_id !== $store->id, 404);
    }
}