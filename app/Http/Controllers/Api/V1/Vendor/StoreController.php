<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Api\V1\Vendor\Concerns\VendorStoreAuthorization;
use App\Http\Requests\Vendor\Store\CreateStoreRequest;
use App\Http\Requests\Vendor\Store\UpdateStoreRequest;
use App\Http\Resources\Vendor\Store\StoreResource;
use App\Models\Store;
use App\Traits\MediaHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StoreController extends BaseApiController
{
    use VendorStoreAuthorization;

    public function index(): AnonymousResourceCollection
    {
        $stores = Store::select('id', 'business_category_id', 'name', 'description', 'logo', 'image')
            ->forAuthVendor()
            ->with('businessCategory:id,name')
            ->useFilters()
            ->latest()
            ->dynamicPaginate();

        return StoreResource::collection($stores);
    }

    public function store(CreateStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['vendor_profile_id'] = auth()->user()->vendorProfile->id;

        $data['image'] = $request->hasFile('image')
            ? MediaHandler::upload($request->file('image'), 'stores/images')
            : null;

        $data['logo']  = $request->hasFile('logo')
            ? MediaHandler::upload($request->file('logo'), 'stores/logos')
            : null;

        $store = Store::create($data);
        $store->load('businessCategory:id,name');
        return $this->apiResponseStored(new StoreResource($store));
    }

    public function update(UpdateStoreRequest $request, Store $store): JsonResponse
    {
        $this->authorizeStore($store);
        $data = $request->validated();

        $data['logo']  = $request->hasFile('logo')
            ? MediaHandler::updateMedia($request->file('logo'), 'stores/logos', $store->logo)
            : $store->logo;

        $data['image'] = $request->hasFile('image')
            ? MediaHandler::updateMedia($request->file('image'), 'stores/images', $store->image)
            : $store->image;

        $store->update($data);
        $store->load('businessCategory:id,name');
        return $this->apiResponseUpdated(new StoreResource($store));
    }

    public function destroy(Store $store): JsonResponse
    {
        $this->authorizeStore($store);
        abort_if($store->branches()->exists(), 403, __('validation.custom.store_has_branches'));
        abort_if($store->products()->exists(), 403, __('validation.custom.store_has_products'));

        $store->logo  ? MediaHandler::deleteMedia($store->logo)  : null;
        $store->image ? MediaHandler::deleteMedia($store->image) : null;

        $store->delete();
        return $this->apiResponseDeleted();
    }
}