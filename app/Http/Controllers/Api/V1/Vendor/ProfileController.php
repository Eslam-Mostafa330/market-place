<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Vendor\Profile\UpdateVendorProfileRequest;
use App\Http\Resources\Vendor\Profile\ProfileResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;

class ProfileController extends BaseApiController
{
    public function __construct(private readonly AuthService $authService) {}

    public function show(): JsonResponse
    {
        $vendor = auth()->user();
        return $this->apiResponseShow(new ProfileResource($vendor));
    }

    /**
     * Get a summary of the vendor's profile for dashboard navbar/sidebar display
     */
    public function showProfileSummary(): JsonResponse
    {
        return $this->apiResponseShow(auth()->user()->only('name'));
    }

    public function update(UpdateVendorProfileRequest $request): JsonResponse
    {
        $vendor = auth()->user();
        $data = $request->validated();
        $this->authService->logoutOtherDevicesOnPasswordChange($vendor, $data, $request);
        $vendor->update($data);
        return $this->apiResponseUpdated(new ProfileResource($vendor));
    }
}