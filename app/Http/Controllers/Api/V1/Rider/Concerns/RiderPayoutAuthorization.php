<?php

namespace App\Http\Controllers\Api\V1\Rider\Concerns;

use App\Models\RiderPayout;

trait RiderPayoutAuthorization
{
    /**
     * Ensure the authenticated rider has assigned to the payout.
     * Prevents a rider from acting on another rider's payout.
     * 
     * @param RiderPayout $payout
     */
    protected function authorizeRiderPayout(RiderPayout $payout): void
    {
        abort_if(
            $payout->rider_id !== auth()->id(),
            404
        );
    }
}