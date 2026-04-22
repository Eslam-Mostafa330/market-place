<?php

namespace App\Observers;

use App\Models\Store;
use Illuminate\Support\Facades\Cache;

class StoreObserver
{
    /**
     * Handle the Store "created" event.
     */
    public function created(Store $store): void
    {
        $this->clearVendorCache($store->vendor_profile_id);
        $this->clearStoreCountCache();
    }

    /**
     * Handle the Store "updating" event.
     */
    public function updating(Store $store): void
    {
        $this->clearStoreCache($store);
    }

    /**
     * Handle the Store "updated" event.
     */
    public function updated(Store $store): void
    {
        $this->clearVendorCache($store->vendor_profile_id);
    }

    /**
     * Handle the Store "deleted" event.
     */
    public function deleted(Store $store): void
    {
        $this->clearStoreCache($store);
        $this->clearVendorCache($store->vendor_profile_id);
        $this->clearStoreCountCache();
    }

    /**
     * Clear all cache entries related to a specific store.
     *
     * This includes Store-specific cache (lookup by ID and slug)
     * Triggered when the store is updated or deleted.
     */
    private function clearStoreCache(Store $store): void
    {
        Cache::forget("store:slug:{$store->getOriginal('slug')}");
        Cache::forget("store:id:{$store->id}");
    }

    /**
     * Clear all the cached data related to a vendor's stores.
     *
     * This includes:
     * - Cached list of store IDs for the vendor
     * - Aggregated store overview used in the dashboard
     *
     * Cleared whenever a store is added, removed, or updated
     */
    private function clearVendorCache(string $vendorProfileId): void
    {
        Cache::forget("vendor_store_ids:{$vendorProfileId}");
        Cache::forget("vendor_stores_overview:{$vendorProfileId}");
    }

    /**
     * Clear cached system-wide counts used in the admin dashboard.
     *
     * This is triggered when a store is created or deleted.
     */
    private function clearStoreCountCache(): void
    {
        Cache::forget('admin_system_counts');
    }
}