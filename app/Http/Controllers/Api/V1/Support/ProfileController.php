<?php

namespace App\Http\Controllers\Api\V1\Support;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Support\Profile\UpdateSupportAgentProfileRequest;
use App\Http\Resources\Support\Profile\ProfileResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;

class ProfileController extends BaseApiController
{
    public function __construct(private readonly AuthService $authService) {}

    public function show(): JsonResponse
    {
        $support = auth()->user();
        return $this->apiResponseShow(new ProfileResource($support));
    }

    /**
     * Get a summary of the support agent's profile for dashboard navbar/sidebar display
     */
    public function showProfileSummary(): JsonResponse
    {
        return $this->apiResponseShow(auth()->user()->only('name'));
    }

    public function update(UpdateSupportAgentProfileRequest $request): JsonResponse
    {
        $support = auth()->user();
        $data = $request->validated();
        $this->authService->logoutOtherDevicesOnPasswordChange($support, $data, $request);
        $support->update($data);
        return $this->apiResponseUpdated(new ProfileResource($support));
    }
}
