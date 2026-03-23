<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Enums\DefineStatus;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Public\StoreBranch\StoreBranchListResource;
use App\Http\Resources\Public\StoreBranch\StoreBranchResource;
use App\Models\Store;
use App\Models\StoreBranch;

class StoreBranchController extends BaseApiController
{
    public function index(Store $store)
    {
        $branches = $store->branches()
            ->select('id', 'name', 'slug', 'city', 'area')
            ->active()
            ->useFilters()
            ->dynamicPaginate();

        return StoreBranchListResource::collection($branches);
    }

    public function show(Store $store, StoreBranch $branch)
    {
        abort_if($branch->status !== DefineStatus::ACTIVE, 404);
        return new StoreBranchResource($branch);
    }
}