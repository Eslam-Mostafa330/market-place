<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Vendor\ProductCategory\ProductCategoryResource;
use App\Models\ProductCategory;

class ProductCategoryController extends BaseApiController
{
    /**
     * To use in the create/update product in the product category dropdown
     */
    public function __invoke()
    {
        $productCategories = ProductCategory::query()
            ->select('id', 'parent_id', 'name')
            ->whereNull('parent_id')
            ->with([
                'children' => fn ($q) => $q
                ->select('id', 'parent_id', 'name')
                ->orderBy('name', 'ASC')
            ])
            ->orderBy('name', 'ASC')
            ->get();

        return $this->apiResponse(ProductCategoryResource::collection($productCategories));
    }
}
