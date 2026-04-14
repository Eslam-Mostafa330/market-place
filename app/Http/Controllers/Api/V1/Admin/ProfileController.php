<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Admin\Profile\UpdateAdminProfileRequest;
use App\Http\Resources\Admin\Profile\ProfileResource;
use App\Services\AuthService;
use App\Traits\ClearsCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProfileController extends BaseApiController
{
    use ClearsCache;

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
        $userId = auth()->id();
    
        $admin = Cache::rememberForever("admin_summary_{$userId}", function () use ($userId) {
            return DB::table('users')
                ->where('id', $userId)
                ->select('name')
                ->first();
        });

        return $this->apiResponseShow($admin);
    }

    public function update(UpdateAdminProfileRequest $request): JsonResponse
    {
        $admin = auth()->user();
        $data = $request->validated();
        $this->authService->logoutOtherDevicesOnPasswordChange($admin, $data, $request);
        $admin->update($data);
        $admin->wasChanged('name') && $this->clearAdminSummaryCache($admin->id);
        return $this->apiResponseUpdated(new ProfileResource($admin));
    }
}