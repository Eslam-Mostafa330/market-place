<?php

namespace App\Http\Controllers\Api\V1\Rider;

use App\Enums\PayoutStatus;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Api\V1\Rider\Concerns\RiderPayoutAuthorization;
use App\Http\Resources\Rider\Payout\PayoutListResource;
use App\Http\Resources\Rider\Payout\PayoutResource;
use App\Models\RiderPayout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PayoutController extends BaseApiController
{
    use RiderPayoutAuthorization;

    public function index(): AnonymousResourceCollection
    {
        $payouts = RiderPayout::select('id', 'rider_id', 'order_id', 'amount', 'status', 'paid_at')
            ->where('rider_id', auth()->id())
            ->with('order:id,order_number')
            ->orderByRaw("status = ? DESC", [PayoutStatus::PENDING->value])
            ->useFilters()
            ->latest()
            ->dynamicPaginate();

        return PayoutListResource::collection($payouts);
    }

    public function show(RiderPayout $payout): JsonResponse
    {
        $this->authorizeRiderPayout($payout);
        $payout->load('order:id,order_number');
        return $this->apiResponseShow(new PayoutResource($payout));
    }
}