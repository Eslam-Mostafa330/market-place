<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\PayoutStatus;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Admin\Payout\MarkCompletePayoutRequest;
use App\Http\Requests\Admin\Payout\UpdatePayoutRequest;
use App\Http\Resources\Admin\Payout\PayoutListResource;
use App\Http\Resources\Admin\Payout\PayoutResource;
use App\Models\RiderPayout;
use App\Services\Payment\PayoutService;
use App\Traits\MediaHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PayoutController extends BaseApiController
{
    public function __construct(private readonly PayoutService $payoutService) {}

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

    public function show(RiderPayout $payout): JsonResponse
    {
        $payout->load(['rider:id,name', 'order:id,order_number', 'processedBy:id,name', 'updatedBy:id,name']);
        return $this->apiResponseShow(new PayoutResource($payout));
    }

    /**
     * Mark a payout as completed.
     */
    public function complete(MarkCompletePayoutRequest $request, RiderPayout $payout): JsonResponse
    {
        $data = $request->validated();

        $data['payout_proof'] = $request->hasFile('payout_proof')
            ? MediaHandler::upload($request->file('payout_proof'), 'payout/images')
            : null;

        $payout = $this->payoutService->completePayout($payout, $data);
        $payout->load(['processedBy:id,name', 'order:id,order_number', 'rider:id,name']);
        return $this->apiResponseUpdated(new PayoutResource($payout));
    }

    /*
    * Update payout details for a completed payout.
    */
    public function update(UpdatePayoutRequest $request, RiderPayout $payout): JsonResponse
    {
        $data = $request->validated();

        $data['payout_proof'] = $request->hasFile('payout_proof')
            ? MediaHandler::updateMedia($request->file('payout_proof'), 'payout/images', $payout->payout_proof)
            : $payout->payout_proof;

        $payout = $this->payoutService->updatePayoutDetails($payout, $data);
        $payout->load(['processedBy:id,name', 'updatedBy:id,name', 'order:id,order_number', 'rider:id,name']);
        return $this->apiResponseUpdated(new PayoutResource($payout));
    }
}