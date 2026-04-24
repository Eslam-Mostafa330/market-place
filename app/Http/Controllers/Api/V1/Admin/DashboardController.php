<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Admin\Dashboard\ValidateFiltersRequest;
use App\Http\Resources\Admin\Activity\ActivityLogResource;
use App\Http\Resources\Admin\Dashboard\LatestReviewResource;
use App\Http\Resources\Admin\Dashboard\MonthlyEarningResource;
use App\Http\Resources\Admin\Dashboard\PeriodStatsResource;
use App\Http\Resources\Admin\Dashboard\TopProductResource;
use App\Http\Resources\Admin\Dashboard\TopStoreResource;
use App\Services\Dashboard\AdminDashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends BaseApiController
{
    public function __construct(private readonly AdminDashboardService $adminDashboardService) {}

    /**
     * Handle the admin dashboard request.
     *
     * Aggregates dashboard data including period statistics, monthly earnings,
     * top products, top stores, latest reviews, and activity logs.
     *
     * Supports optional year and month filters (defaults to current period).
     */
    public function __invoke(ValidateFiltersRequest $request): JsonResponse
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