<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Admin\BusinessCategory\CreateBusinessCategoryRequest;
use App\Http\Requests\Admin\BusinessCategory\UpdateBusinessCategoryRequest;
use App\Http\Resources\Admin\BusinessCategory\BusinessCategoryResource;
use App\Models\BusinessCategory;
use App\Traits\MediaHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BusinessCategoryController extends BaseApiController
{
    public function index(): AnonymousResourceCollection
    {
        $businessCategories = BusinessCategory::select('id', 'name', 'description', 'image')
            ->withCount('stores')
            ->useFilters()
            ->latest()
            ->get();

        return BusinessCategoryResource::collection($businessCategories);
    }

    public function store(CreateBusinessCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();

        $data['image'] = $request->hasFile('image')
            ? MediaHandler::upload($request->file('image'), 'business-categories/images')
            : null;

        $businessCategory = BusinessCategory::create($data);
        return $this->apiResponseStored(new BusinessCategoryResource($businessCategory));
    }

    public function update(UpdateBusinessCategoryRequest $request, BusinessCategory $businessCategory): JsonResponse
    {
        $data = $request->validated();

        $data['image'] = $request->hasFile('image')
            ? MediaHandler::updateMedia($request->file('image'), 'business-categories/images', $businessCategory->image)
            : $businessCategory->image;

        $businessCategory->update($data);
        $businessCategory->loadCount('stores');
        return $this->apiResponseUpdated(new BusinessCategoryResource($businessCategory));
    }

    public function destroy(BusinessCategory $businessCategory): JsonResponse
    {
        abort_if($businessCategory->stores()->exists(), 422, __('business-categories.cannot_delete_due_stores'));
        $businessCategory->image ? MediaHandler::deleteMedia($businessCategory->image) : null;
        $businessCategory->delete();
        return $this->apiResponseDeleted();
    }
}
