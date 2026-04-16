<?php

namespace App\Services\Review;

use App\Models\Review;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

class AdminReviewService
{
    /**
     * Deletes a review and recalculates the store rating.
     */
    public function deleteReview(Review $review): void
    {
        DB::transaction(function () use ($review) {
            $store = Store::select('id', 'average_rating', 'reviews_count')
                ->where('id', $review->store_id)
                ->lockForUpdate()
                ->firstOrFail();

            $review->delete();

            $this->recalculateStoreRating($store, $review->rate);
        });
    }

    /**
     * Decrement store rating after deleting a review.
     */
    private function recalculateStoreRating(Store $store, int $deletedRate): void
    {
        $newCount = $store->reviews_count - 1;

        $newAverage = $newCount > 0
            ? (($store->average_rating * $store->reviews_count) - $deletedRate) / $newCount
            : 0;

        $store->update([
            'average_rating' => round($newAverage, 1),
            'reviews_count'  => $newCount,
        ]);
    }
}