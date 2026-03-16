<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Admin\StoreProduct\ProductListResource;
use App\Http\Resources\Admin\StoreProduct\ProductResource;
use App\Models\Product;
use App\Models\Store;
use App\Traits\MediaHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StoreProductController extends BaseApiController
{
    public function index(Store $store): AnonymousResourceCollection
    {
        $products = Product::select('id', 'product_category_id', 'name', 'image', 'price', 'quantity', 'status')
            ->where('store_id', $store->id)
            ->with('productCategory:id,name')
            ->latest()
            ->useFilters()
            ->dynamicPaginate();

        return ProductListResource::collection($products);
    }

    public function show(Store $store, Product $product): JsonResponse
    {
        $product->load('productCategory:id,name');
        return $this->apiResponse(new ProductResource($product));
    }

    public function destroy(Store $store, Product $product): JsonResponse
    {
        $product->image ? MediaHandler::deleteMedia($product->image) : null;
        $product->delete();
        return $this->apiResponseDeleted();
    }
}