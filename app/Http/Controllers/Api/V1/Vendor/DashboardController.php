<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Vendor\Dashboard\ValidateFiltersRequest;
use App\Http\Resources\Vendor\Dashboard\LatestReviewResource;
use App\Http\Resources\Vendor\Dashboard\MonthlyEarningResource;
use App\Http\Resources\Vendor\Dashboard\PeriodStatsResource;
use App\Http\Resources\Vendor\Dashboard\StoreOverviewResource;
use App\Http\Resources\Vendor\Dashboard\TopProductResource;
use App\Models\VendorProfile;
use App\Services\Dashboard\VendorDashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends BaseApiController
{
    public function __construct(private readonly VendorDashboardService $vendorDashboardService) {}

    /**
     * Handle the vendor dashboard request.
     *
     * Aggregates dashboard data including period statistics, stores overview,
     * monthly earnings, top products, and latest reviews.
     *
     * Optionally filters by store_id (validated against vendor stores).
     * Returns 404 if the store does not belong to the vendor.
     */
    public function __invoke(ValidateFiltersRequest $request): JsonResponse
    {
        $request->validated();

        $vendorProfileId = VendorProfile::where('user_id', $request->user()->id)->value('id');
        $year            = $request->integer('year',  now()->year);
        $month           = $request->integer('month', now()->month);
        $storeId         = $request->input('store_id');

        $storeIds = $this->vendorDashboardService->resolveStoreIds($vendorProfileId, $storeId);
        abort_if($storeId && empty($storeIds), 404);

        return $this->apiResponseShow([
            'period_stats'     => new PeriodStatsResource($this->vendorDashboardService->getPeriodStatistics($storeIds, $year, $month)),
            'stores'           => StoreOverviewResource::collection($this->vendorDashboardService->getStoresOverview($vendorProfileId)),
            'monthly_earnings' => MonthlyEarningResource::collection($this->vendorDashboardService->getMonthlyEarnings($storeIds, $year)),
            'top_products'     => TopProductResource::collection($this->vendorDashboardService->getTopProducts($storeIds, $year, $month)),
            'latest_reviews'   => LatestReviewResource::collection($this->vendorDashboardService->getRecentReviews($storeIds)),
        ]);
    }
}