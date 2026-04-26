<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Admin\Store\UpdateCommissionRateRequest;
use App\Http\Resources\Admin\Store\CommissionRateResource;
use App\Http\Resources\Admin\Store\StoreResource;
use App\Models\Store;
use App\Traits\MediaHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StoreController extends BaseApiController
{
    public function index(): AnonymousResourceCollection
    {
        $stores = Store::select('id', 'business_category_id', 'vendor_profile_id', 'name', 'description', 'commission_rate', 'logo', 'image')
            ->with([
                'businessCategory:id,name',
                'vendorProfile.user:id,name',
            ])
            ->useFilters()
            ->latest()
            ->dynamicPaginate();

        return StoreResource::collection($stores);
    }

    public function updateCommission(UpdateCommissionRateRequest $request, Store $store): JsonResponse
    {
        $data = $request->validated();
        $store->update(['commission_rate' => $data['commission_rate']]);
        return $this->apiResponseUpdated(new CommissionRateResource($store));
    }

    public function destroy(Store $store): JsonResponse
    {
        abort_if($store->branches()->exists(), 422, __('stores.cannot_delete_due_branches'));
        $store->logo  ? MediaHandler::deleteMedia($store->logo)  : null;
        $store->image ? MediaHandler::deleteMedia($store->image) : null;
        $store->delete();
        return $this->apiResponseDeleted();
    }
}