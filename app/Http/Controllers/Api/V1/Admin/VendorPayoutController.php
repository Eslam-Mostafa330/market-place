<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\PayoutStatus;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Admin\VendorPayout\MarkCompletePayoutRequest;
use App\Http\Requests\Admin\VendorPayout\UpdatePayoutRequest;
use App\Http\Resources\Admin\VendorPayout\PayoutListResource;
use App\Http\Resources\Admin\VendorPayout\PayoutResource;
use App\Models\VendorPayout;
use App\Services\Payment\VendorPayoutService;
use App\Traits\MediaHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VendorPayoutController extends BaseApiController
{
    public function __construct(private readonly VendorPayoutService $vendorPayoutService) {}

    public function index(): AnonymousResourceCollection
    {
        $payouts = VendorPayout::select('id', 'vendor_id', 'order_id', 'amount', 'status', 'paid_at')
            ->with(['vendor:id,name', 'order:id,order_number'])
            ->orderByRaw("status = ? DESC", [PayoutStatus::PENDING->value])
            ->useFilters()
            ->latest()
            ->dynamicPaginate();

        return PayoutListResource::collection($payouts);
    }

    public function show(VendorPayout $payout): JsonResponse
    {
        $payout->load(['vendor:id,name', 'order:id,order_number', 'processedBy:id,name', 'updatedBy:id,name']);
        return $this->apiResponseShow(new PayoutResource($payout));
    }

    /**
     * Mark a payout as completed.
     */
    public function complete(MarkCompletePayoutRequest $request, VendorPayout $vendorPayout): JsonResponse
    {
        $data = $request->validated();

        $data['payout_proof'] = $request->hasFile('payout_proof')
            ? MediaHandler::upload($request->file('payout_proof'), 'vendor-payout/images')
            : null;

        $payout = $this->vendorPayoutService->completePayout($vendorPayout, $data);
        $payout->load(['processedBy:id,name', 'order:id,order_number', 'vendor:id,name']);
        return $this->apiResponseUpdated(new PayoutResource($payout));
    }

    /*
    * Update payout details for a completed payout.
    */
    public function update(UpdatePayoutRequest $request, VendorPayout $vendorPayout): JsonResponse
    {
        $data = $request->validated();

        $data['payout_proof'] = $request->hasFile('payout_proof')
            ? MediaHandler::updateMedia($request->file('payout_proof'), 'vendor-payout/images', $vendorPayout->payout_proof)
            : $vendorPayout->payout_proof;

        $payout = $this->vendorPayoutService->updatePayoutDetails($vendorPayout, $data);
        $payout->load(['processedBy:id,name', 'updatedBy:id,name', 'order:id,order_number', 'vendor:id,name']);
        return $this->apiResponseUpdated(new PayoutResource($payout));
    }
}
