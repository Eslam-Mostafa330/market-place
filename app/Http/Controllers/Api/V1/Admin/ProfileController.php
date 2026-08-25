<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Admin\Profile\UpdateAdminProfileRequest;
use App\Http\Resources\Admin\Profile\ProfileResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;

class ProfileController extends BaseApiController
{
    public function __construct(private readonly AuthService $authService) {}

    public function show(): JsonResponse
    {
        $admin = auth()->user();
        return $this->apiResponseShow(new ProfileResource($admin));
    }

    /**
     * Get a summary of the admin's profile for dashboard navbar/sidebar display
     */
    public function showProfileSummary(): JsonResponse
    {
        return $this->apiResponseShow(auth()->user()->only('name'));
    }

    public function update(UpdateAdminProfileRequest $request): JsonResponse
    {
        $admin = auth()->user();
        $data = $request->validated();
        $this->authService->logoutOtherDevicesOnPasswordChange($admin, $data, $request);
        $admin->update($data);
        return $this->apiResponseUpdated(new ProfileResource($admin));
    }
}