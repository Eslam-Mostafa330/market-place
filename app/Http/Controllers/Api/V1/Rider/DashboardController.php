<?php

namespace App\Http\Controllers\Api\V1\Rider;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Rider\Dashboard\ValidateFiltersRequest;
use App\Http\Resources\Rider\Dashboard\LatestDeliveryResource;
use App\Http\Resources\Rider\Dashboard\LatestPayoutResource;
use App\Http\Resources\Rider\Dashboard\MonthlyEarningResource;
use App\Http\Resources\Rider\Dashboard\PeriodStatsResource;
use App\Services\Dashboard\RiderDashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends BaseApiController
{
    public function __construct(private readonly RiderDashboardService $riderDashboardService) {}

    /**
     * Handle the rider dashboard request.
     *
     * Aggregates all data required for the rider dashboard, including
     * period statistics, monthly earnings, latest deliveries,
     * and pending payouts.
     *
     * Optional filters (year, month) default to the current date.
     */
    public function __invoke(ValidateFiltersRequest $request): JsonResponse
    {
        $riderId = auth()->id();
        $year    = $request->integer('year',  now()->year);
        $month   = $request->integer('month', now()->month);

        return $this->apiResponseShow([
            'period_stats'      => new PeriodStatsResource($this->riderDashboardService->getPeriodStatistics($riderId, $year, $month)),
            'monthly_earnings'  => MonthlyEarningResource::collection($this->riderDashboardService->getMonthlyEarnings($riderId, $year)),
            'latest_deliveries' => LatestDeliveryResource::collection($this->riderDashboardService->getLatestDeliveries($riderId)),
            'latest_payouts'    => LatestPayoutResource::collection($this->riderDashboardService->getLatestPendingPayouts($riderId)),
        ]);
    }
}