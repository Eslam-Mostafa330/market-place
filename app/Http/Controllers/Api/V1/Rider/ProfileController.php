<?php

namespace App\Http\Controllers\Api\V1\Rider;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Rider\Profile\UpdateRiderProfileRequest;
use App\Http\Resources\Rider\Profile\ProfileResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;

class ProfileController extends BaseApiController
{
    public function __construct(private readonly AuthService $authService) {}

    public function show(): JsonResponse
    {
        $rider = auth()->user();
        $rider->load('riderProfile:id,user_id,license_number,license_expiry,vehicle_type,vehicle_number,total_deliveries');
        return $this->apiResponseShow(new ProfileResource($rider));
    }

    /**
     * Get a summary of the rider's profile for dashboard navbar/sidebar display
     */
    public function showProfileSummary(): JsonResponse
    {
        return $this->apiResponseShow(auth()->user()->only('name'));
    }

    public function update(UpdateRiderProfileRequest $request): JsonResponse
    {
        $rider = auth()->user();
        $data = $request->validated();
        $this->authService->logoutOtherDevicesOnPasswordChange($rider, $data, $request);
        $rider->update($data);
        return $this->apiResponseUpdated(new ProfileResource($rider));
    }
}