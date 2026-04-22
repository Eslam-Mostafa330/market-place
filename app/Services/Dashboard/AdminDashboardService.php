<?php

namespace App\Services\Dashboard;

use App\Enums\DefineStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PayoutStatus;
use App\Enums\UserRole;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class AdminDashboardService
{
    private const MONTH_LABELS = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
    ];

    /**
     * Retrieve aggregated dashboard statistics for a given month and year.
     *
     * Includes order metrics, payment breakdown, platform earnings,
     * entity counts, and pending payout totals.
     *
     * @param int $year
     * @param int $month
     * @return object
     */
    public function getPeriodStatistics(int $year, int $month): object
    {
        $delivered = OrderStatus::DELIVERED->value;
        $cancelled = OrderStatus::CANCELLED->value;
        $cash      = PaymentMethod::CASH->value;
        $visa      = PaymentMethod::VISA->value;

        $stats = DB::table('orders')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->selectRaw("
                COUNT(*) AS total_orders,
                SUM(CASE WHEN order_status = {$delivered} THEN 1 ELSE 0 END) AS delivered_orders,
                SUM(CASE WHEN order_status = {$cancelled} THEN 1 ELSE 0 END) AS cancelled_orders,
                SUM(CASE WHEN payment_method = {$cash} THEN 1 ELSE 0 END) AS cash_orders,
                SUM(CASE WHEN payment_method = {$visa} THEN 1 ELSE 0 END) AS visa_orders,
                SUM(CASE WHEN payment_method = {$visa} AND order_status = {$delivered} THEN total ELSE 0 END) AS visa_earned,
                SUM(CASE WHEN order_status = {$delivered} THEN commission_amount ELSE 0 END) AS platform_commission,
                ROUND(AVG(CASE WHEN order_status = {$delivered} THEN total END), 2) AS average_order_value
            ")
            ->first();

        $counts = $this->getDashboardEntityCounts();

        $stats->stores_count         = $counts->stores_count;
        $stats->active_coupons_count = $counts->active_coupons_count;
        $stats->admins_count         = $counts->admins_count;
        $stats->vendors_count        = $counts->vendors_count;
        $stats->customers_count      = $counts->customers_count;
        $stats->riders_count         = $counts->riders_count;

        $payouts = $this->getPendingPayoutTotals();

        $stats->pending_vendor_payouts = $payouts->vendor_pending;
        $stats->pending_rider_payouts  = $payouts->rider_pending;

        return $stats;
    }

    /**
     * Retrieve system-wide entity counts used in the admin dashboard.
     *
     * Includes stores, active coupons, and users grouped by role.
     * Cached for 10 days to reduce repeated database load.
     *
     * @return object
     */
    private function getDashboardEntityCounts(): object
    {
        $active   = DefineStatus::ACTIVE->value;
        $admin    = UserRole::ADMIN->value;
        $vendor   = UserRole::VENDOR->value;
        $customer = UserRole::CUSTOMER->value;
        $rider    = UserRole::RIDER->value;

        return Cache::remember('admin_system_counts', now()->addDays(10), fn () =>
            DB::selectOne("
                SELECT
                    (SELECT COUNT(*) FROM stores) AS stores_count,
                    (SELECT COUNT(*) FROM coupons WHERE status = {$active} AND (expires_at IS NULL OR expires_at > NOW())) AS active_coupons_count,
                    SUM(CASE WHEN role = {$admin} THEN 1 ELSE 0 END) AS admins_count,
                    SUM(CASE WHEN role = {$vendor} THEN 1 ELSE 0 END) AS vendors_count,
                    SUM(CASE WHEN role = {$customer} THEN 1 ELSE 0 END) AS customers_count,
                    SUM(CASE WHEN role = {$rider} THEN 1 ELSE 0 END) AS riders_count
                FROM users
            ")
        );
    }

    /**
     * Retrieve the total pending payout amounts for vendors and riders.
     *
     * Combines both payout tables into a single query for optimal performance.
     *
     * @return object
     */
    private function getPendingPayoutTotals(): object
    {
        $pending = PayoutStatus::PENDING->value;

        return DB::selectOne("
            SELECT
                SUM(CASE WHEN source = 'vendor' THEN amount ELSE 0 END) AS vendor_pending,
                SUM(CASE WHEN source = 'rider' THEN amount ELSE 0 END) AS rider_pending
            FROM (
                SELECT amount, 'vendor' AS source FROM vendor_payouts WHERE status = {$pending}
                UNION ALL
                SELECT amount, 'rider' AS source FROM rider_payouts WHERE status = {$pending}
            ) AS payouts
        ");
    }

    /**
     * Retrieve monthly platform earnings for a given year.
     *
     * Returns all 12 months including months with zero earnings.
     *
     * @param int $year
     * @return Collection
     */
    public function getMonthlyEarnings(int $year): Collection
    {
        $rows = DB::table('orders')
            ->where('order_status', OrderStatus::DELIVERED->value)
            ->whereYear('created_at', $year)
            ->selectRaw('MONTH(created_at) AS month_number, SUM(commission_amount) AS earned, COUNT(*) AS orders_count')
            ->groupByRaw('MONTH(created_at)')
            ->get()
            ->keyBy('month_number');

        return collect(self::MONTH_LABELS)->map(fn ($label, $number) => (object) [
            'month'        => $label,
            'earned'       => (float) ($rows[$number]->earned ?? 0),
            'orders_count' => (int) ($rows[$number]->orders_count ?? 0),
        ])->values();
    }

    /**
     * Retrieve the top selling products for a given period.
     *
     * Products are ranked by total sold quantity.
     *
     * @param int $year
     * @param int|null $month
     * @return Collection
     */
    public function getTopProducts(int $year, ?int $month): Collection
    {
        $query = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.order_status', OrderStatus::DELIVERED->value)
            ->whereYear('orders.created_at', $year);

        if ($month) {
            $query->whereMonth('orders.created_at', $month);
        }

        return $query->selectRaw('
                order_items.product_id,
                order_items.product_name,
                SUM(order_items.quantity) AS total_quantity,
                SUM(order_items.subtotal) AS total_revenue
            ')
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->orderByDesc('total_quantity')
            ->limit(6)
            ->get();
    }

    /**
     * Retrieve the top performing stores for a given period.
     *
     * Stores are ranked by total revenue.
     *
     * @param int $year
     * @param int|null $month
     * @return Collection
     */
    public function getTopStores(int $year, ?int $month): Collection
    {
        $query = DB::table('orders')
            ->join('stores', 'orders.store_id', '=', 'stores.id')
            ->where('orders.order_status', OrderStatus::DELIVERED->value)
            ->whereYear('orders.created_at', $year);

        if ($month) {
            $query->whereMonth('orders.created_at', $month);
        }

        return $query->selectRaw('
                orders.store_id,
                stores.name AS store_name,
                COUNT(*) AS orders_count,
                SUM(orders.total) AS total_revenue,
                SUM(orders.vendor_earnings) AS vendor_earnings
            ')
            ->groupBy('orders.store_id', 'stores.name')
            ->orderByDesc('total_revenue')
            ->limit(6)
            ->get();
    }

    /**
     * Retrieve the latest customer reviews.
     *
     * Cached for 15 days and invalidated via observer.
     *
     * @return Collection
     */
    public function getRecentReviews(): Collection
    {
        return Cache::remember('admin_latest_reviews', now()->addDays(15), fn () =>
            DB::table('reviews')
                ->join('users AS customers', 'reviews.customer_id', '=', 'customers.id')
                ->join('stores', 'reviews.store_id', '=', 'stores.id')
                ->select(
                    'reviews.id',
                    'reviews.rate',
                    'reviews.full_review',
                    'reviews.created_at AS reviewed_at',
                    'customers.name AS customer',
                    'stores.name AS store_name',
                    'stores.id AS store_id',
                )
                ->latest('reviews.created_at')
                ->limit(10)
                ->get()
        );
    }

    /**
     * Retrieve the most recent activity logs for the admin dashboard.
     *
     * Includes the causer relation and latest activity metadata.
     *
     * @return Collection
     */
    public function getRecentActivityLogs(): Collection
    {
        return Activity::select('id', 'subject_type', 'subject_id', 'causer_type', 'causer_id', 'properties', 'event', 'created_at')
            ->with('causer:id,name')
            ->latest()
            ->limit(10)
            ->get();
    }
}