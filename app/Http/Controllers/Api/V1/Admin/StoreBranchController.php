<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Admin\StoreBranch\StoreBranchListResource;
use App\Http\Resources\Admin\StoreBranch\StoreBranchResource;
use App\Models\Store;
use App\Models\StoreBranch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StoreBranchController extends BaseApiController
{
    public function index(Store $store): AnonymousResourceCollection
    {
        $branches = StoreBranch::select('id', 'name', 'city', 'phone', 'status', 'created_at')
            ->where('store_id', $store->id)
            ->latest()
            ->useFilters()
            ->dynamicPaginate();

        return StoreBranchListResource::collection($branches);
    }

    public function show(Store $store, StoreBranch $branch): JsonResponse
    {
        return $this->apiResponse(new StoreBranchResource($branch));
    }

    public function destroy(Store $store, StoreBranch $branch): JsonResponse
    {
        $branch->delete();
        return $this->apiResponseDeleted();
    }
}