<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\PayoutStatus;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Admin\RiderPayout\MarkCompletePayoutRequest;
use App\Http\Requests\Admin\RiderPayout\UpdatePayoutRequest;
use App\Http\Resources\Admin\RiderPayout\PayoutListResource;
use App\Http\Resources\Admin\RiderPayout\PayoutResource;
use App\Models\RiderPayout;
use App\Services\Payment\RiderPayoutService;
use App\Traits\MediaHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RiderPayoutController extends BaseApiController
{
    public function __construct(private readonly RiderPayoutService $riderPayoutService) {}

    public function index(): AnonymousResourceCollection
    {
        $payouts = RiderPayout::select('id', 'rider_id', 'order_id', 'amount', 'status', 'paid_at')
            ->with(['rider:id,name', 'order:id,order_number'])
            ->orderByRaw("status = ? DESC", [PayoutStatus::PENDING->value])
            ->useFilters()
            ->latest()
            ->dynamicPaginate();

        return PayoutListResource::collection($payouts);
    }

    public function show(RiderPayout $riderPayout): JsonResponse
    {
        $riderPayout->load(['rider:id,name', 'order:id,order_number', 'processedBy:id,name', 'updatedBy:id,name']);
        return $this->apiResponseShow(new PayoutResource($riderPayout));
    }

    /**
     * Mark a payout as completed.
     */
    public function complete(MarkCompletePayoutRequest $request, RiderPayout $riderPayout): JsonResponse
    {
        $data = $request->validated();

        $data['payout_proof'] = $request->hasFile('payout_proof')
            ? MediaHandler::upload($request->file('payout_proof'), 'payout/images')
            : null;

        $payout = $this->riderPayoutService->completePayout($riderPayout, $data);
        $payout->load(['processedBy:id,name', 'order:id,order_number', 'rider:id,name']);
        return $this->apiResponseUpdated(new PayoutResource($payout));
    }

    /*
    * Update payout details for a completed payout.
    */
    public function update(UpdatePayoutRequest $request, RiderPayout $riderPayout): JsonResponse
    {
        $data = $request->validated();

        $data['payout_proof'] = $request->hasFile('payout_proof')
            ? MediaHandler::updateMedia($request->file('payout_proof'), 'payout/images', $riderPayout->payout_proof)
            : $riderPayout->payout_proof;

        $payout = $this->riderPayoutService->updatePayoutDetails($riderPayout, $data);
        $payout->load(['processedBy:id,name', 'updatedBy:id,name', 'order:id,order_number', 'rider:id,name']);
        return $this->apiResponseUpdated(new PayoutResource($payout));
    }
}