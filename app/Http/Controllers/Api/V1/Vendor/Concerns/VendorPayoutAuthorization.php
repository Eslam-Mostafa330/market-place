<?php

namespace App\Http\Controllers\Api\V1\Vendor\Concerns;

use App\Models\VendorPayout;

trait VendorPayoutAuthorization
{
    /**
     * Ensure the authenticated vendor has assigned to the payout.
     * Prevents a vendor from acting on another vendor's payout.
     * 
     * @param VendorPayout $payout
     */
    protected function authorizeVendorPayout(VendorPayout $payout): void
    {
        abort_if(
            $payout->vendor_id !== auth()->id(),
            404
        );
    }
}