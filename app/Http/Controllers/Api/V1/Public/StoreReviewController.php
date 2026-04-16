<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Filters\PublicReviewFilters;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Public\Review\ReviewResource;
use App\Models\Store;

class StoreReviewController extends BaseApiController
{
    public function __invoke(Store $store)
    {
        $reviews = $store->reviews()
            ->with('customer:id,name')
            ->select('id', 'customer_id', 'rate', 'full_review', 'created_at')
            ->latest()
            ->useFilters(PublicReviewFilters::class)
            ->dynamicPaginate();

        return ReviewResource::collection($reviews);
    }
}