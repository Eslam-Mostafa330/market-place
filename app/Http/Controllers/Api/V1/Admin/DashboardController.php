<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Admin\Dashboard\ValidateFiltersRequest;
use App\Http\Resources\Admin\Dashboard\ActivityLogResource;
use App\Http\Resources\Admin\Dashboard\LatestReviewResource;
use App\Http\Resources\Admin\Dashboard\MonthlyEarningResource;
use App\Http\Resources\Admin\Dashboard\PeriodStatsResource;
use App\Http\Resources\Admin\Dashboard\TopProductResource;
use App\Http\Resources\Admin\Dashboard\TopStoreResource;
use App\Services\Dashboard\AdminDashboardService;

class DashboardController extends BaseApiController
{
    public function __construct(private readonly AdminDashboardService $adminDashboardService) {}

    /**
     * Handle the admin dashboard request.
     *
     * This endpoint aggregates all data required for the admin dashboard,
     * including period statistics, monthly earnings, top products,
     * top stores, latest reviews, and recent activity logs.
     *
     * Date filtering is applied based on the provided year and month:
     * - If no filters are provided, the current year and month are used by default.
     * - If both year and month are provided, all metrics are scoped to that period.
     */
    public function __invoke(ValidateFiltersRequest $request)
    {
        $year     = $request->integer('year',  now()->year);
        $month    = $request->integer('month', now()->month);
        $hasMonth = $request->filled('month');

        return $this->apiResponseShow([
            'period_stats'     => new PeriodStatsResource($this->adminDashboardService->getPeriodStatistics($year, $month)),
            'monthly_earnings' => MonthlyEarningResource::collection($this->adminDashboardService->getMonthlyEarnings($year)),
            'top_products'     => TopProductResource::collection($this->adminDashboardService->getTopProducts($year, $hasMonth ? $month : null)),
            'top_stores'       => TopStoreResource::collection($this->adminDashboardService->getTopStores($year, $hasMonth ? $month : null)),
            'latest_reviews'   => LatestReviewResource::collection($this->adminDashboardService->getRecentReviews()),
            'activity_logs'    => ActivityLogResource::collection($this->adminDashboardService->getRecentActivityLogs()),
        ]);
    }
}