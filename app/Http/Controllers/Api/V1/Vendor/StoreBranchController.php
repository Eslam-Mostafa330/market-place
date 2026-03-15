<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Enums\DefineStatus;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Api\V1\Vendor\Concerns\VendorStoreAuthorization;
use App\Http\Requests\Vendor\StoreBranch\CreateStoreBranchRequest;
use App\Http\Requests\Vendor\StoreBranch\UpdateStoreBranchRequest;
use App\Http\Resources\Vendor\StoreBranch\StoreBranchListResource;
use App\Http\Resources\Vendor\StoreBranch\StoreBranchResource;
use App\Http\Resources\Vendor\StoreBranch\ToggleStoreBranchStatusResource;
use App\Models\Store;
use App\Models\StoreBranch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StoreBranchController extends BaseApiController
{
    use VendorStoreAuthorization;

    public function index(Store $store): AnonymousResourceCollection
    {
        $this->authorizeStore($store);

        $branches = StoreBranch::select('id', 'name', 'city', 'phone', 'status', 'created_at')
            ->where('store_id', $store->id)
            ->latest()
            ->useFilters()
            ->dynamicPaginate();

        return StoreBranchListResource::collection($branches);
    }

    public function store(CreateStoreBranchRequest $request, Store $store): JsonResponse
    {
        $this->authorizeStore($store);
        $data = $request->validated();
        $data['store_id'] = $store->id;
        $branch = StoreBranch::create($data);
        return $this->apiResponseStored(new StoreBranchResource($branch));
    }

    public function show(Store $store, StoreBranch $branch): JsonResponse
    {
        $this->authorizeStore($store);

        return $this->apiResponse(new StoreBranchResource($branch));
    }

    public function update(UpdateStoreBranchRequest $request, Store $store, StoreBranch $branch): JsonResponse
    {
        $this->authorizeStore($store);
        $data = $request->validated();
        $branch->update($data);
        return $this->apiResponseUpdated(new StoreBranchResource($branch));
    }

    public function destroy(Store $store, StoreBranch $branch): JsonResponse
    {
        $this->authorizeStore($store);
        $branch->delete();
        return $this->apiResponseDeleted();
    }

    /**
     * Toggles the branch status between active and inactive.
     */
    public function toggleStatus(Store $store, StoreBranch $branch): JsonResponse
    {
        $this->authorizeStore($store);
        
        $newStatus = $branch->status === DefineStatus::ACTIVE
            ? DefineStatus::INACTIVE
            : DefineStatus::ACTIVE;

        $branch->update(['status' => $newStatus]);
        return $this->apiResponseUpdated(new ToggleStoreBranchStatusResource($branch));
    }
}