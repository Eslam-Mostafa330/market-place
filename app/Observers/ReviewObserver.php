<?php

namespace App\Observers;

use App\Models\Review;
use Illuminate\Support\Facades\Cache;

class ReviewObserver
{
    /**
     * Handle the Review "created" event.
     */
    public function created(Review $review): void
    {
        $this->clearStoreReviewCache($review->store_id);
    }

    /**
     * Handle the Review "updated" event.
     */
    public function updated(Review $review): void
    {
        $this->clearStoreReviewCache($review->store_id);
    }

    /**
     * Handle the Review "deleted" event.
     */
    public function deleted(Review $review): void
    {
        $this->clearStoreReviewCache($review->store_id);
    }

    /**
     * Clear cached latest reviews for a specific store.
     *
     * This cache stores the most recent reviews per store and is used
     * in the vendor dashboard. It's invalidated whenever a review
     * is created, updated, or deleted.
     */
    private function clearStoreReviewCache(string $storeId): void
    {
        Cache::forget("store_reviews:{$storeId}");
        Cache::forget('admin_latest_reviews');
    }
}