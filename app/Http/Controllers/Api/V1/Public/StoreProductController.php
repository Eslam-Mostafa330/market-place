<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Enums\DefineStatus;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Public\StoreProduct\StoreProductListResource;
use App\Http\Resources\Public\StoreProduct\StoreProductResource;
use App\Models\Product;
use App\Models\Store;

class StoreProductController extends BaseApiController
{
    public function index(Store $store)
    {
        $products = $store->products()
            ->select('products.id', 'products.name', 'products.slug', 'products.image', 'products.price', 'products.sale_price')
            ->active()
            ->useFilters()
            ->dynamicPaginate();

        return StoreProductListResource::collection($products);
    }

    public function show(Store $store, Product $product)
    {
        abort_if($product->status !== DefineStatus::ACTIVE, 404);

        $product->setRelation('relatedProducts', $store->products()
            ->select('id', 'name', 'slug', 'image', 'price', 'sale_price')
            ->where('product_category_id', $product->product_category_id)
            ->where('id', '!=', $product->id)
            ->active()
            ->limit(8)
            ->get()
        );

        return new StoreProductResource($product);
    }
}