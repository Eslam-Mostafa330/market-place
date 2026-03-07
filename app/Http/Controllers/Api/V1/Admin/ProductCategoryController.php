<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Admin\ProductCategory\CreateProductCategoryRequest;
use App\Http\Requests\Admin\ProductCategory\UpdateProductCategoryRequest;
use App\Http\Resources\Admin\ProductCategory\ProductCategoryResource;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductCategoryController extends BaseApiController
{
    public function index(): AnonymousResourceCollection
    {
        $categories = ProductCategory::select('id', 'parent_id', 'name')
            ->with('children:id,parent_id,name')
            ->whereNull('parent_id')
            ->latest()
            ->get();

        return ProductCategoryResource::collection($categories);
    }

    public function store(CreateProductCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $productCategory = ProductCategory::create($data);
        return $this->apiResponseStored(new ProductCategoryResource($productCategory));
    }

    public function update(UpdateProductCategoryRequest $request, ProductCategory $productCategory): JsonResponse
    {
        $data = $request->validated();
        $productCategory->update($data);
        return $this->apiResponseUpdated(new ProductCategoryResource($productCategory));
    }

    public function destroy(ProductCategory $productCategory): JsonResponse
    {
        abort_if($productCategory->products()->exists(), 422, __('validation.custom.cannot_delete_product_category'));
        abort_if($productCategory->children()->exists(), 422, __('validation.custom.cannot_delete_category_subcategories'));
        $productCategory->delete();
        return $this->apiResponseDeleted();
    }
}
