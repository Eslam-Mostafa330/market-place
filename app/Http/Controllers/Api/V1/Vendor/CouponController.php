<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Enums\DefineStatus;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Api\V1\Vendor\Concerns\VendorStoreAuthorization;
use App\Http\Requests\Vendor\Coupon\CreateCouponRequest;
use App\Http\Requests\Vendor\Coupon\UpdateCouponRequest;
use App\Http\Resources\Vendor\Coupon\CouponListResource;
use App\Http\Resources\Vendor\Coupon\CouponResource;
use App\Http\Resources\Vendor\Coupon\ToggleCouponStatusResource;
use App\Models\Coupon;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CouponController extends BaseApiController
{
    use VendorStoreAuthorization;

    public function index(Store $store): AnonymousResourceCollection
    {
        $this->authorizeStore($store);

        $coupons = Coupon::select('id', 'name', 'code', 'coupon_type', 'value', 'expires_at', 'status')
            ->where('store_id', $store->id)
            ->useFilters()
            ->latest()
            ->dynamicPaginate();

        return CouponListResource::collection($coupons);
    }

    public function store(CreateCouponRequest $request, Store $store): JsonResponse
    {
        $this->authorizeStore($store);
        $data = $request->validated();
        $coupon = $store->coupons()->create($data);
        return $this->apiResponseStored(new CouponResource($coupon));
    }

    public function show(Store $store, Coupon $coupon): JsonResponse
    {
        $this->authorizeStore($store);

        return $this->apiResponse(new CouponResource($coupon));
    }

    public function update(UpdateCouponRequest $request, Store $store, Coupon $coupon): JsonResponse
    {
        $this->authorizeStore($store);
        $data = $request->validated();
        $coupon->update($data);
        return $this->apiResponseUpdated(new CouponResource($coupon));
    }

    public function destroy(Store $store, Coupon $coupon): JsonResponse
    {
        $this->authorizeStore($store);
        $coupon->delete();
        return $this->apiResponseDeleted();
    }

    /**
     * Toggles the coupon status between active and inactive.
     */
    public function toggleStatus(Store $store, Coupon $coupon): JsonResponse
    {
        $this->authorizeStore($store);
        
        $newStatus = $coupon->status === DefineStatus::ACTIVE
            ? DefineStatus::INACTIVE
            : DefineStatus::ACTIVE;

        $coupon->update(['status' => $newStatus]);
        return $this->apiResponseUpdated(new ToggleCouponStatusResource($coupon));
    }
}