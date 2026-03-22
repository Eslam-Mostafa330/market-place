<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\Public\Store\StoreResource;
use App\Models\BusinessCategory;
use App\Models\Store;

class StoreController extends Controller
{
    public function index(string $slug)
    {
        $category = BusinessCategory::where('slug', $slug)->select('id')->firstOrFail();

        $stores = $category->stores()
            ->select('id', 'name', 'slug', 'logo')
            ->dynamicPaginate();

        return StoreResource::collection($stores);
    }

    public function show(BusinessCategory $businessCategory, Store $store)
    {
        $store->load('vendorProfile:id,business_name,rating,total_orders,created_at');
        return new StoreResource($store);
    }
}