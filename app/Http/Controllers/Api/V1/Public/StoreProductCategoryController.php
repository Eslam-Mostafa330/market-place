<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\ProductCategory;
use App\Models\Store;

class StoreProductCategoryController extends BaseApiController
{
    /**
     * Retrieve and structure product categories for a given store.
     *
     * Fetches all product categories associated with the store's products.
     * Groups categories by their parent_id to organize them hierarchically.
     * contains its children categories as a nested "children" relation.
     * Returns a flattened list of parent categories, each with its related children.
     */
    public function __invoke(Store $store)
    {
        $categories = ProductCategory::select('id', 'name', 'slug', 'parent_id')
            ->whereIn('id', $store->products()->select('product_category_id'))
            ->with('parent:id,name,slug')
            ->get()
            ->groupBy('parent_id')
            ->map(fn($children) => $children->first()->parent?->setRelation('children', $children->map->only('id', 'name', 'slug')))
            ->values();

        return $this->apiResponseShow($categories);
    }
}