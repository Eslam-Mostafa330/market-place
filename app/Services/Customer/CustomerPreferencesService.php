<?php

namespace App\Services\Customer;

use App\Enums\DefineStatus;
use App\Models\CustomerProfile;
use App\Models\Favorite;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductView;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class CustomerPreferencesService
{
    /**
     * Limits defining how many product IDs each algorithm contributes.
     * The total across all sources is capped to maintain a consistent
     * and bounded preference list size.
     */
    private const RECENTLY_VIEWED_LIMIT   = 5;
    private const FAVORITED_LIMIT         = 10;
    private const REORDERED_LIMIT         = 10;
    private const CATEGORY_AFFINITY_LIMIT = 8;
    private const STORE_LOYALTY_LIMIT     = 7;
    private const TOTAL_LIMIT             = 40;

    /**
     * Performs a full rebuild of the customer's preferences.
     *
     * This method aggregates product IDs from all recommendation signals:
     * recently viewed, favorited, reordered items, category affinity,
     * and store loyalty. The resulting list is deduplicated, ordered by
     * priority (merge order), and trimmed to the configured total limit.
     *
     * Intended to run as a scheduled job (e.g., nightly) to ensure
     * long-term consistency and correction of any drift.
     */
    public function rebuildAll(CustomerProfile $profile): void
    {
        $customerId        = $profile->user_id;
        $orderedProductIds = $this->getOrderedProductIds($customerId);

        $preferences = collect()
            ->merge($this->getRecentlyViewedIds($customerId))
            ->merge($this->getFavoritedIds($customerId))
            ->merge($this->getReorderedIds($customerId))
            ->merge($this->getCategoryAffinityIds($customerId, $orderedProductIds))
            ->merge($this->getStoreLoyaltyIds($customerId, $orderedProductIds))
            ->unique()
            ->take(self::TOTAL_LIMIT)
            ->values();

        $profile->update(['preferences' => $preferences]);
    }

    /**
     * Updates preferences based on recently viewed products.
     *
     * Retrieves the most recent product views and merges them into the
     * existing preferences, prioritizing them while preserving other
     * recommendation signals.
     *
     * Triggered after a product view action.
     */
    public function refreshRecentlyViewed(CustomerProfile $profile): void
    {
        $fresh = $this->getRecentlyViewedIds($profile->user_id);
        $this->mergeIntoPreferences($profile, $fresh);
    }

    /**
     * Updates preferences based on favorited products.
     *
     * Retrieves the latest favorited items and merges them into the
     * existing preferences, giving them higher priority.
     *
     * Triggered after a favorite/unfavorite action.
     */
    public function refreshFavorited(CustomerProfile $profile): void
    {
        $fresh = $this->getFavoritedIds($profile->user_id);
        $this->mergeIntoPreferences($profile, $fresh);
    }

    /**
     * Updates preference signals affected by order activity.
     *
     * This includes:
     * - Frequently reordered products
     * - Category affinity (top categories by order volume)
     * - Store loyalty (top stores by order frequency)
     *
     * Only these signals are recalculated to avoid unnecessary work.
     *
     * Triggered after a successful order placement.
     */
    public function refreshAfterOrder(CustomerProfile $profile): void
    {
        $customerId        = $profile->user_id;
        $orderedProductIds = $this->getOrderedProductIds($customerId);

        $fresh = collect()
            ->merge($this->getReorderedIds($customerId))
            ->merge($this->getCategoryAffinityIds($customerId, $orderedProductIds))
            ->merge($this->getStoreLoyaltyIds($customerId, $orderedProductIds));

        $this->mergeIntoPreferences($profile, $fresh);
    }

    /**
     * Merges newly computed product IDs into existing preferences.
     *
     * Fresh results are placed at the front (higher priority), followed
     * by the existing preferences. The final list is deduplicated and
     * truncated to the maximum allowed size.
     *
     * This ensures partial updates enhance preferences without
     * overwriting signals from other algorithms.
     */
    private function mergeIntoPreferences(CustomerProfile $profile, Collection $fresh): void
    {
        $existing = collect($profile->preferences ?? []);

        $merged = $fresh
            ->merge($existing)
            ->unique()
            ->take(self::TOTAL_LIMIT)
            ->values();

        $profile->update(['preferences' => $merged]);
    }

    /**
     * Retrieves the most recently viewed product IDs for the customer.
     *
     * Results are ordered by the latest interaction timestamp and limited
     * to a small subset to prioritize recency.
     */
    private function getRecentlyViewedIds(string $customerId): Collection
    {
        return ProductView::where('customer_id', $customerId)
            ->orderBy('viewed_at', 'DESC')
            ->limit(self::RECENTLY_VIEWED_LIMIT)
            ->pluck('product_id');
    }

    /**
     * Retrieves the most recently favorited product IDs.
     *
     * Represents explicit user interest and is ordered by the most recent
     * favorite actions.
     */
    private function getFavoritedIds(string $customerId): Collection
    {
        return Favorite::where('customer_id', $customerId)
            ->latest()
            ->limit(self::FAVORITED_LIMIT)
            ->pluck('product_id');
    }

    /**
     * Retrieves products that the customer has reordered multiple times.
     *
     * Products are ranked by frequency of reorders, highlighting strong
     * purchase intent and repeat behavior.
     */
    private function getReorderedIds(string $customerId): Collection
    {
        return OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.customer_id', $customerId)
            ->select('order_items.product_id', DB::raw('COUNT(*) as times_ordered'))
            ->groupBy('order_items.product_id')
            ->having('times_ordered', '>', 1)
            ->orderBy('times_ordered', 'DESC')
            ->limit(self::REORDERED_LIMIT)
            ->pluck('order_items.product_id');
    }

    /**
     * Retrieves products from the customer's most frequently ordered categories.
     *
     * Identifies the top categories based on historical orders and returns
     * active products from those categories, excluding already ordered items.
     *
     * Encourages discovery within familiar domains.
     */
    private function getCategoryAffinityIds(string $customerId, Collection $orderedProductIds): Collection
    {
        $topCategoryIds = OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.customer_id', $customerId)
            ->select('products.product_category_id', DB::raw('COUNT(*) as total'))
            ->groupBy('products.product_category_id')
            ->orderBy('total', 'DESC')
            ->limit(2)
            ->pluck('products.product_category_id');

        return Product::whereIn('product_category_id', $topCategoryIds)
            ->whereNotIn('id', $orderedProductIds)
            ->where('status', DefineStatus::ACTIVE)
            ->orderBy('is_featured', 'DESC')
            ->limit(self::CATEGORY_AFFINITY_LIMIT)
            ->pluck('id');
    }

    /**
     * Retrieves products from the customer's most frequently ordered stores.
     *
     * Identifies top stores based on order count and returns active products
     * from those stores, excluding previously ordered items.
     *
     * Reinforces engagement with preferred stores.
     */
    private function getStoreLoyaltyIds(string $customerId, Collection $orderedProductIds): Collection
    {
        $topStoreIds = Order::where('customer_id', $customerId)
            ->select('store_id', DB::raw('COUNT(*) as total'))
            ->groupBy('store_id')
            ->orderBy('total', 'DESC')
            ->limit(3)
            ->pluck('store_id');

        return Product::whereIn('store_id', $topStoreIds)
            ->whereNotIn('id', $orderedProductIds)
            ->where('status', DefineStatus::ACTIVE)
            ->orderBy('is_featured', 'DESC')
            ->limit(self::STORE_LOYALTY_LIMIT)
            ->pluck('id');
    }

    /**
     * Retrieves a capped list of product IDs that the customer has ordered.
     *
     * This dataset is reused across multiple algorithms to exclude previously
     * purchased items, avoiding redundant queries and improving performance.
     */
    private function getOrderedProductIds(string $customerId): Collection
    {
        return OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.customer_id', $customerId)
            ->orderBy('order_items.created_at', 'DESC')
            ->limit(200)
            ->pluck('order_items.product_id');
    }
}