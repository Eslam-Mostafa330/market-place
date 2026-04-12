<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Enums\PayoutStatus;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Api\V1\Vendor\Concerns\VendorPayoutAuthorization;
use App\Http\Resources\Vendor\Payout\PayoutListResource;
use App\Http\Resources\Vendor\Payout\PayoutResource;
use App\Models\VendorPayout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PayoutController extends BaseApiController
{
    use VendorPayoutAuthorization;

    public function index(): AnonymousResourceCollection
    {
        $payouts = VendorPayout::select('id', 'vendor_id', 'order_id', 'amount', 'status', 'paid_at')
            ->where('vendor_id', auth()->id())
            ->with('order:id,order_number')
            ->orderByRaw("status = ? DESC", [PayoutStatus::PENDING->value])
            ->useFilters()
            ->latest()
            ->dynamicPaginate();

        return PayoutListResource::collection($payouts);
    }

    public function show(VendorPayout $payout): JsonResponse
    {
        $this->authorizeVendorPayout($payout);
        $payout->load('order:id,order_number');
        return $this->apiResponseShow(new PayoutResource($payout));
    }
}