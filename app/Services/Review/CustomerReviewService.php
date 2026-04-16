<?php

namespace App\Services\Review;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Review;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class CustomerReviewService
{
    /**
     * Create a review for a completed order.
     *
     * Ensures the order belongs to the customer and is delivered.
     * Creates the review and updates the store rating atomically.
     */
    public function createReview(string $orderId, int $rate, ?string $fullReview, string $customerId): Review 
    {
        $order = $this->resolveEligibleOrder($orderId, $customerId);

        return DB::transaction(function () use ($order, $customerId, $rate, $fullReview) {
            $store = Store::where('id', $order->store_id)
                ->lockForUpdate()
                ->firstOrFail();

            $review = Review::create([
                'customer_id' => $customerId,
                'store_id'    => $store->id,
                'order_id'    => $order->id,
                'rate'        => $rate,
                'full_review' => $fullReview,
            ]);

            $this->calculateStoreRatingOnCreate($store, $rate);

            return $review;
        });
    }

    /**
     * Update an existing review within the allowed time window.
     *
     * Ensures the review belongs to the customer and is still editable.
     * Updates the review and adjusts the store rating accordingly.
     */
    public function updateReview(Review $review, int $rate, ?string $fullReview, string $customerId): Review
    {
        $this->ensureReviewOwnedByCustomer($review, $customerId);

        $this->ensureReviewEditable($review);

        return DB::transaction(function () use ($review, $rate, $fullReview) {
            $store = Store::select(['id', 'average_rating', 'reviews_count'])
                ->where('id', $review->store_id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldRate = $review->rate;
            $review->update([
                'rate'        => $rate,
                'full_review' => $fullReview,
            ]);

            $this->recalculateStoreRatingOnEdit($store, $oldRate, $rate);

            return $review;
        });
    }

    /**
     * Retrieve an order eligible for review by the given customer.
     *
     * Ensures the order exists, belongs to the customer, and is delivered.
     */
    private function resolveEligibleOrder(string $orderId, string $customerId): Order
    {
        $order = Order::select(['id', 'store_id', 'order_status'])
            ->where('id', $orderId)
            ->where('customer_id', $customerId)
            ->firstOrFail();

        $this->ensureOrderDelivered($order);

        return $order;
    }

    /**
     * Update store rating after creating a new review.
     *
     * Adjusts the average rating incrementally without recalculating all reviews.
     */
    private function calculateStoreRatingOnCreate(Store $store, int $newRate): void
    {
        $newCount = $store->reviews_count + 1;

        $newAverage = (($store->average_rating * $store->reviews_count) + $newRate) / $newCount;

        DB::table('stores')->where('id', $store->id)->update([
            'average_rating' => round($newAverage, 1),
            'reviews_count'  => $newCount,
        ]);
    }

    /**
     * Update store rating after editing a review.
     *
     * Adjusts the average rating incrementally.
     * Skips recalculation if the rating has not changed.
     */
    private function recalculateStoreRatingOnEdit(Store $store, int $oldRate, int $newRate): void
    {
        if ($oldRate === $newRate) {
            return;
        }

        $correctedAverage = (($store->average_rating * $store->reviews_count) - $oldRate + $newRate) / $store->reviews_count;

        DB::table('stores')->where('id', $store->id)->update([
            'average_rating' => round($correctedAverage, 1),
        ]);
    }

    /**
     * Ensure the review belongs to the given customer.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    private function ensureReviewOwnedByCustomer(Review $review, string $customerId): void
    {
        if ($review->customer_id !== $customerId) {
            throw new NotFoundHttpException();
        }
    }

    /**
     * Ensure the review is editable.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
     */
    private function ensureReviewEditable(Review $review): void
    {
        if ($review->created_at->diffInHours(now()) > 24) {
            throw new UnprocessableEntityHttpException(__('reviews.edit_window_expired'));
        }
    }

    /**
     * Ensure the order has been delivered before allowing a review.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
     */
    private function ensureOrderDelivered(Order $order): void
    {
        if ($order->order_status !== OrderStatus::DELIVERED) {
            throw new UnprocessableEntityHttpException(__('reviews.order_not_completed'));
        }
    }
}