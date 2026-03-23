<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Public\BusinessCategory\BusinessCategoryResource;
use App\Models\BusinessCategory;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BusinessCategoryController extends BaseApiController
{
    public function __invoke(): AnonymousResourceCollection
    {
        $businessCategories = BusinessCategory::select('id', 'name', 'slug', 'description', 'image')
            ->orderBy('name', 'ASC')
            ->get();

        return BusinessCategoryResource::collection($businessCategories);
    }
}