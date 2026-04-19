<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Vendor\Dashboard\ValidateFiltersRequest;
use App\Http\Resources\Vendor\Dashboard\LatestReviewResource;
use App\Http\Resources\Vendor\Dashboard\MonthlyEarningResource;
use App\Http\Resources\Vendor\Dashboard\PeriodStatsResource;
use App\Http\Resources\Vendor\Dashboard\StoreOverviewResource;
use App\Http\Resources\Vendor\Dashboard\TopProductResource;
use App\Services\Dashboard\VendorDashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends BaseApiController
{
    public function __construct(private readonly VendorDashboardService $vendorDashboardService) {}

    /**
     * Handle the vendor dashboard request.
     *
     * This endpoint aggregates all data required for the vendor dashboard,
     * including period statistics, stores overview, monthly earnings,
     * top products, and latest reviews.
     *
     * Store filtering is optional:
     * - If a store_id is provided, it is validated against the vendor's cached store IDs.
     * - If the store does not belong to the vendor, a 404 response is returned.
     *
     * The stores overview always returns all vendor stores, while the rest of the
     * data can be scoped to a specific store if requested.
     */
    public function __invoke(ValidateFiltersRequest $request): JsonResponse
    {
        $request->validated();

        $vendorProfileId = $request->user()->vendorProfile->id;
        $year            = $request->integer('year',  now()->year);
        $month           = $request->integer('month', now()->month);
        $storeId         = $request->input('store_id');

        $storeIds = $this->vendorDashboardService->resolveStoreIds($vendorProfileId, $storeId);
        abort_if($storeId && empty($storeIds), 404);

        return $this->apiResponseShow([
            'period_stats'     => new PeriodStatsResource($this->vendorDashboardService->getPeriodStats($storeIds, $year, $month)),
            'stores'           => StoreOverviewResource::collection($this->vendorDashboardService->getStoresOverview($vendorProfileId)),
            'monthly_earnings' => MonthlyEarningResource::collection($this->vendorDashboardService->getMonthlyEarnings($storeIds, $year)),
            'top_products'     => TopProductResource::collection($this->vendorDashboardService->getTopProducts($storeIds, $year, $month)),
            'latest_reviews'   => LatestReviewResource::collection($this->vendorDashboardService->getLatestReviews($storeIds)),
        ]);
    }
}