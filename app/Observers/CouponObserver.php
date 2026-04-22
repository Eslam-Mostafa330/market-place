<?php

namespace App\Observers;

use App\Models\Coupon;
use Illuminate\Support\Facades\Cache;

class CouponObserver
{
    /**
     * Handle the Coupon "created" event.
     */
    public function created(Coupon $coupon): void
    {
        $this->clearCouponCountCache();
    }

    /**
     * Handle the Coupon "updated" event.
     */
    public function updated(Coupon $coupon): void
    {
        $this->clearCouponCountCache();
    }

    /**
     * Handle the Coupon "deleted" event.
     */
    public function deleted(Coupon $coupon): void
    {
        $this->clearCouponCountCache();
    }

    /**
     * Clear cached system-wide counts used in the admin dashboard.
     *
     * This is triggered when a coupon is created, updated, or deleted.
     */
    private function clearCouponCountCache(): void
    {
        Cache::forget('admin_system_counts');
    }
}
