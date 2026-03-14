<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Vendor\Store\CreateStoreRequest;
use App\Http\Requests\Vendor\Store\UpdateStoreRequest;
use App\Http\Resources\Vendor\Store\StoreResource;
use App\Models\Store;
use App\Models\VendorProfile;
use App\Traits\MediaHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StoreController extends BaseApiController
{
    public function index(): AnonymousResourceCollection
    {
        $stores = Store::select('id', 'business_category_id', 'name', 'description', 'logo', 'image')
            ->where('vendor_profile_id', $this->vendorProfile()->id)
            ->with('businessCategory:id,name')
            ->useFilters()
            ->latest()
            ->dynamicPaginate();

        return StoreResource::collection($stores);
    }

    public function store(CreateStoreRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['vendor_profile_id'] = $this->vendorProfile()->id;

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
        abort_if($store->vendor_profile_id !== $this->vendorProfile()->id, 403);
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
        abort_if($store->vendor_profile_id !== $this->vendorProfile()->id, 403);

        $store->logo  ? MediaHandler::deleteMedia($store->logo)  : null;
        $store->image ? MediaHandler::deleteMedia($store->image) : null;

        $store->delete();
        return $this->apiResponseDeleted();
    }

    /**
     * Retrieves the authenticated vendor's profile once and reuses it across methods.
     * Scoping all queries to this profile ensures vendors can only access their own stores.
     */
    private function vendorProfile(): VendorProfile
    {
        return VendorProfile::where('user_id', auth()->id())->firstOrFail();
    }
}