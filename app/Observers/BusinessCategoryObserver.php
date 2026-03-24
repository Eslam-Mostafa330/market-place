<?php

namespace App\Observers;

use App\Models\BusinessCategory;
use Illuminate\Support\Facades\Cache;

class BusinessCategoryObserver
{
    /**
     * Handle the BusinessCategory "created" event.
     */
    public function created(BusinessCategory $businessCategory): void
    {
        //
    }

    /**
     * Handle the BusinessCategory "updating" event.
     */
    public function updating(BusinessCategory $businessCategory): void
    {
        $this->clearCache($businessCategory);
    }

    /**
     * Handle the BusinessCategory "updated" event.
     */
    public function updated(BusinessCategory $businessCategory): void
    {
        //
    }

    /**
     * Handle the BusinessCategory "deleted" event.
     */
    public function deleted(BusinessCategory $businessCategory): void
    {
        $this->clearCache($businessCategory);
    }

    /**
     * Handle the BusinessCategory "restored" event.
     */
    public function restored(BusinessCategory $businessCategory): void
    {
        //
    }

    /**
     * Handle the BusinessCategory "force deleted" event.
     */
    public function forceDeleted(BusinessCategory $businessCategory): void
    {
        //
    }

    private function clearCache(BusinessCategory $businessCategory): void
    {
        Cache::forget("business_category:slug:{$businessCategory->getOriginal('slug')}");
        Cache::forget("business_category:id:{$businessCategory->id}");
    }
}