<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Enums\DefineStatus;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Api\V1\Vendor\Concerns\VendorStoreAuthorization;
use App\Http\Requests\Vendor\Product\CreateProductRequest;
use App\Http\Requests\Vendor\Product\UpdateProductRequest;
use App\Http\Resources\Vendor\Product\ProductListResource;
use App\Http\Resources\Vendor\Product\ProductResource;
use App\Http\Resources\Vendor\Product\ToggleProductStatusResource;
use App\Models\Product;
use App\Models\Store;
use App\Traits\MediaHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends BaseApiController
{
    use VendorStoreAuthorization;

    public function index(Store $store): AnonymousResourceCollection
    {
        $this->authorizeStore($store);

        $products = Product::select('id', 'product_category_id', 'name', 'image', 'price', 'quantity', 'status')
            ->where('store_id', $store->id)
            ->with('productCategory:id,name')
            ->latest()
            ->useFilters()
            ->dynamicPaginate();

        return ProductListResource::collection($products);
    }

    public function store(CreateProductRequest $request, Store $store): JsonResponse
    {
        $this->authorizeStore($store);

        $data = $request->validated();
        $data['store_id'] = $store->id;

        $data['image'] = $request->hasFile('image')
            ? MediaHandler::upload($request->file('image'), 'products/images')
            : null;

        $product = Product::create($data);
        $product->load('productCategory:id,name');
        return $this->apiResponseStored(new ProductResource($product));
    }

    public function show(Store $store, Product $product): JsonResponse
    {
        $this->authorizeStore($store);
        $product->load('productCategory:id,name');
        return $this->apiResponse(new ProductResource($product));
    }

    public function update(UpdateProductRequest $request, Store $store, Product $product): JsonResponse
    {
        $this->authorizeStore($store);
        $data = $request->validated();

        $data['image'] = $request->hasFile('image')
            ? MediaHandler::updateMedia($request->file('image'), 'products/images', $product->image)
            : $product->image;

        $product->update($data);
        $product->load('productCategory:id,name');
        return $this->apiResponseUpdated(new ProductResource($product));
    }

    public function destroy(Store $store, Product $product): JsonResponse
    {
        $this->authorizeStore($store);
        $product->image ? MediaHandler::deleteMedia($product->image) : null;
        $product->delete();
        return $this->apiResponseDeleted();
    }

    /**
     * Toggles the product status between active and inactive.
     */
    public function toggleStatus(Store $store, Product $product): JsonResponse
    {
        $this->authorizeStore($store);

        $newStatus = $product->status === DefineStatus::ACTIVE
            ? DefineStatus::INACTIVE
            : DefineStatus::ACTIVE;

        $product->update(['status' => $newStatus]);
        return $this->apiResponseUpdated(new ToggleProductStatusResource($product));
    }
}