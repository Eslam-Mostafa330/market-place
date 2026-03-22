<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Public\Store\StoreResource;
use App\Models\BusinessCategory;
use App\Models\Store;

class StoreController extends Controller
{
    public function index(BusinessCategory $businessCategory)
    {
        $stores = $businessCategory->stores()
            ->select('id', 'name', 'slug', 'image', 'logo', 'description')
            ->useFilters()
            ->dynamicPaginate();

        return StoreResource::collection($stores);
    }

    public function show(BusinessCategory $businessCategory, Store $store)
    {
        $store->load([
            'vendorProfile:id,business_name,rating,total_orders,created_at',
            'activeBranches' => fn ($q) => $q->limit(3),
        ])->loadCount('activeBranches');

        return new StoreResource($store);
    }
}