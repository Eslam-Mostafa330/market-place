<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Customer\Review\StoreReviewRequest;
use App\Http\Requests\Customer\Review\UpdateReviewRequest;
use App\Http\Resources\Customer\Review\ReviewResource;
use App\Models\Review;
use App\Services\Review\CustomerReviewService;
use Illuminate\Http\JsonResponse;

class StoreReviewController extends BaseApiController
{
    public function __construct(private readonly CustomerReviewService $customerReviewService) {}

    public function store(StoreReviewRequest $request, string $orderId): JsonResponse
    {
        $data = $request->validated();

        $review = $this->customerReviewService->createReview(
            orderId:    $orderId,
            rate:       $data['rate'],
            fullReview: $data['full_review'] ?? null,
            customerId: auth()->id(),
        );

        return $this->apiResponseStored(new ReviewResource($review));
    }

    public function update(UpdateReviewRequest $request, Review $review): JsonResponse
    {
        $data = $request->validated();

        $review = $this->customerReviewService->updateReview(
            review:     $review,
            rate:       $data['rate'],
            fullReview: $data['full_review'] ?? null,
            customerId: auth()->id(),
        );

        return $this->apiResponseUpdated(new ReviewResource($review));
    }
}