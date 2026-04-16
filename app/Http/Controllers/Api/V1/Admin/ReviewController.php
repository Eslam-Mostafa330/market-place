<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Filters\AdminReviewFilters;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Admin\Review\ReviewResource;
use App\Models\Review;
use App\Services\Review\AdminReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReviewController extends BaseApiController
{
    public function __construct(private readonly AdminReviewService $adminReviewService) {}

    public function index(): AnonymousResourceCollection
    {
        $reviews = Review::with('store:id,name', 'customer:id,name')
            ->latest()
            ->useFilters(AdminReviewFilters::class)
            ->dynamicPaginate();
        
        return ReviewResource::collection($reviews);
    }

    public function destroy(Review $review): JsonResponse
    {
        $this->adminReviewService->deleteReview($review);

        return $this->apiResponseDeleted();
    }
}