<?php

namespace App\Services\Dashboard;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PayoutStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RiderDashboardService
{
    private const MONTH_LABELS = [
        1 => 'Jan', 2 => 'Feb', 3  => 'Mar', 4  => 'Apr',
        5 => 'May', 6 => 'Jun', 7  => 'Jul', 8  => 'Aug',
        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
    ];

    /**
     * Retrieve aggregated statistics for a rider within a specific year and month.
     *
     * Includes total orders, delivered orders, total earnings, payment method breakdown,
     * earnings by payment method, and average earning per delivered order.
     *
     * @param string $riderId
     * @param int $year
     * @param int $month
     * @return object|null
     */
    public function getPeriodStatistics(string $riderId, int $year, int $month): object
    {
        $delivered = OrderStatus::DELIVERED->value;
        $cash      = PaymentMethod::CASH->value;
        $visa      = PaymentMethod::VISA->value;

        return DB::table('orders')
            ->where('rider_id', $riderId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->selectRaw("
                COUNT(*) AS total_orders,
                SUM(CASE WHEN order_status = {$delivered} THEN 1 ELSE 0 END) AS delivered_orders,
                SUM(CASE WHEN order_status = {$delivered} THEN rider_earnings ELSE 0 END) AS total_earned,
                SUM(CASE WHEN payment_method = {$cash} THEN 1 ELSE 0 END) AS cash_orders,
                SUM(CASE WHEN payment_method = {$visa} THEN 1 ELSE 0 END) AS visa_orders,
                SUM(CASE WHEN payment_method = {$cash} AND order_status = {$delivered} THEN rider_earnings ELSE 0 END) AS cash_earned,
                SUM(CASE WHEN payment_method = {$visa} AND order_status = {$delivered} THEN rider_earnings ELSE 0 END) AS visa_earned,
                ROUND(AVG(CASE WHEN order_status = {$delivered} THEN rider_earnings END), 2) AS average_earning_per_delivery
            ")
            ->first();
    }
    
    /**
     * Retrieve monthly earnings summary for a rider within a given year.
     *
     * Returns a collection of all months with their corresponding total earnings
     * and number of delivered orders. Months with no data are included with zero values.
     *
     * @param string $riderId
     * @param int $year
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function getMonthlyEarnings(string $riderId, int $year): Collection
    {
        $rows = DB::table('orders')
            ->where('rider_id', $riderId)
            ->where('order_status', OrderStatus::DELIVERED->value)
            ->whereYear('created_at', $year)
            ->selectRaw('MONTH(created_at) AS month_number, SUM(rider_earnings) AS earned, COUNT(*) AS orders_count')
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
     * Retrieve the most recent delivered orders for a rider.
     *
     * Includes order details such as order number, earnings, payment method,
     * delivery location, delivery timestamp, and associated store name.
     *
     * @param string $riderId
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function getLatestDeliveries(string $riderId): Collection
    {
        return DB::table('orders')
            ->join('stores', 'orders.store_id', '=', 'stores.id')
            ->where('orders.rider_id', $riderId)
            ->where('orders.order_status', OrderStatus::DELIVERED->value)
            ->select(
                'orders.id',
                'orders.order_number',
                'orders.rider_earnings',
                'orders.payment_method',
                'orders.delivery_city',
                'orders.delivery_state',
                'orders.delivered_at',
                'stores.name AS store_name',
            )
            ->latest('orders.delivered_at')
            ->limit(10)
            ->get();
    }

    /**
     * Retrieve the most recent pending payouts for a rider.
     *
     * Includes payout details such as amount, status, creation date,
     * and the related order number.
     *
     * @param string $riderId
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function getLatestPendingPayouts(string $riderId): Collection
    {
        return DB::table('rider_payouts')
            ->join('orders', 'rider_payouts.order_id', '=', 'orders.id')
            ->where('rider_payouts.rider_id', $riderId)
            ->where('rider_payouts.status', PayoutStatus::PENDING->value)
            ->select(
                'rider_payouts.id',
                'rider_payouts.amount',
                'rider_payouts.status',
                'rider_payouts.created_at',
                'orders.order_number',
            )
            ->latest('rider_payouts.created_at')
            ->limit(6)
            ->get();
    }
}