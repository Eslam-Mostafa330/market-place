<?php

namespace App\Services\Dashboard;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PayoutStatus;
use App\Models\Order;
use App\Models\Review;
use App\Models\Store;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VendorDashboardService
{
    public const MONTH_LABELS = [
        1 => 'Jan', 2 => 'Feb', 3  => 'Mar', 4  => 'Apr',
        5 => 'May', 6 => 'Jun', 7  => 'Jul', 8  => 'Aug',
        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
    ];

    /**
     * Resolve the list of store IDs accessible to a vendor.
     *
     * The full list is cached to avoid repeated database queries.
     * If a specific store ID is provided, it is validated against
     * the cached list to ensure it belongs to the vendor.
     *
     * Returns either all store IDs or a single valid store ID.
     */
    public function resolveStoreIds(string $vendorProfileId, ?string $storeId): array
    {
        $allIds = Cache::remember("vendor_store_ids:{$vendorProfileId}", now()->addDays(60),
            fn () => Store::where('vendor_profile_id', $vendorProfileId)
                ->pluck('id')
                ->all()
        );

        if ($storeId) {
            return in_array($storeId, $allIds, true) ? [$storeId] : [];
        }

        return $allIds;
    }

    /**
     * Retrieve a cached overview of all stores for a vendor.
     *
     * This returns basic store metrics used in the dashboard and is
     * cached per vendor. The data always includes all vendor stores,
     * regardless of any store-level filtering applied elsewhere.
     */
    public function getStoresOverview(string $vendorProfileId): Collection
    {
        return Cache::remember("vendor_stores_overview:{$vendorProfileId}", now()->addDays(60),
            fn () => Store::where('vendor_profile_id', $vendorProfileId)
                ->select('id', 'name', 'average_rating', 'reviews_count')
                ->get()
        );
    }

    /**
     * Calculate aggregated order and revenue statistics for a given period.
     *
     * This always queries fresh data to ensure financial accuracy.
     * It includes totals, breakdowns by status and payment method, and
     * derived metrics such as average order value and pending payouts.
     */
    public function getPeriodStats(array $storeIds, int $year, int $month): object
    {
        $delivered = OrderStatus::DELIVERED->value;
        $cancelled = OrderStatus::CANCELLED->value;
        $cash      = PaymentMethod::CASH->value;
        $visa      = PaymentMethod::VISA->value;

        $stats = Order::whereIn('store_id', $storeIds)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->selectRaw("
                COUNT(*) as total_orders,
                SUM(CASE WHEN order_status = {$delivered} THEN vendor_earnings  ELSE 0 END) as total_earned,
                SUM(CASE WHEN order_status = {$delivered} THEN commission_amount ELSE 0 END) as total_commission,
                SUM(CASE WHEN order_status = {$delivered} THEN 1 ELSE 0 END) as delivered_orders,
                SUM(CASE WHEN order_status = {$cancelled} THEN 1 ELSE 0 END) as cancelled_orders,
                SUM(CASE WHEN payment_method = {$cash}    THEN 1 ELSE 0 END) as cash_orders,
                SUM(CASE WHEN payment_method = {$visa}    THEN 1 ELSE 0 END) as visa_orders,
                SUM(CASE WHEN payment_method = {$cash} AND order_status = {$delivered} THEN vendor_earnings ELSE 0 END) as cash_earned,
                SUM(CASE WHEN payment_method = {$visa} AND order_status = {$delivered} THEN vendor_earnings ELSE 0 END) as visa_earned,
                ROUND(AVG(CASE WHEN order_status = {$delivered} THEN total END), 2) as average_order_value
            ")
            ->first();

        $stats->pending_payout = $this->getPendingPayoutAmount($storeIds);

        return $stats;
    }

    /**
     * Compute the total pending payout amount for delivered orders.
     *
     * This sums vendor earnings for orders that are marked as delivered
     * but still have pending payout status, providing insight into
     * outstanding balances owed to the vendor.
     */
    private function getPendingPayoutAmount(array $storeIds): float
    {
        return (float) DB::table('orders')
            ->join('vendor_payouts', 'vendor_payouts.order_id', '=', 'orders.id')
            ->whereIn('orders.store_id', $storeIds)
            ->where('orders.order_status', OrderStatus::DELIVERED->value)
            ->where('vendor_payouts.status', PayoutStatus::PENDING->value)
            ->sum('orders.vendor_earnings');
    }

    /**
     * Generate a monthly earnings breakdown for a given year.
     *
     * Aggregates total earnings and order counts per month, ensuring
     * all months are represented even if no data exists for some.
     * Useful for charts and trend analysis in the dashboard.
     */
    public function getMonthlyEarnings(array $storeIds, int $year): Collection
    {
        $rows = Order::whereIn('store_id', $storeIds)
            ->where('order_status', OrderStatus::DELIVERED->value)
            ->whereYear('created_at', $year)
            ->selectRaw('MONTH(created_at) as month_number, SUM(vendor_earnings) as earned, COUNT(*) as orders_count')
            ->groupByRaw('MONTH(created_at)')
            ->get()
            ->keyBy('month_number');

        return collect(self::MONTH_LABELS)->map(fn ($label, $number) => (object) [
            'month'        => $label,
            'earned'       => (float) ($rows[$number]->earned ?? 0),
            'orders_count' => (int)   ($rows[$number]->orders_count ?? 0),
        ])->values();
    }

    /**
     * Retrieve the top-performing products for a given period.
     *
     * Products are ranked by total quantity sold, along with their
     * generated revenue. This helps highlight best-selling items
     * for the vendor within the selected timeframe.
     */
    public function getTopProducts(array $storeIds, int $year, int $month): Collection
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('orders.store_id', $storeIds)
            ->where('orders.order_status', OrderStatus::DELIVERED->value)
            ->whereYear('orders.created_at', $year)
            ->whereMonth('orders.created_at', $month)
            ->selectRaw('
                order_items.product_id,
                order_items.product_name,
                SUM(order_items.quantity) as total_quantity,
                SUM(order_items.subtotal) as total_revenue
            ')
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->orderBy('total_quantity', 'DESC')
            ->limit(6)
            ->get();
    }

    /**
     * Retrieve the latest reviews for the given stores.
     *
     * Reviews are cached per store to allow independent invalidation.
     * The results are merged into a single collection for display
     * in the dashboard, ensuring both performance and data freshness.
     */
    public function getLatestReviews(array $storeIds): Collection
    {
        return collect($storeIds)->flatMap(function ($storeId) {
            return Cache::remember("store_reviews:{$storeId}", now()->addDays(15),
                fn () => Review::where('store_id', $storeId)
                    ->select('id', 'store_id', 'customer_id', 'rate', 'full_review', 'created_at')
                    ->with(['customer:id,name', 'store:id,name'])
                    ->latest()
                    ->limit(10)
                    ->get()
            );
        });
    }
}