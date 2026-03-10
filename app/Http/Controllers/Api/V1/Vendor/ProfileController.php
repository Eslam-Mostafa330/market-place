<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Vendor\Profile\UpdateVendorProfileRequest;
use App\Http\Resources\Vendor\Profile\ProfileResource;
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
        $vendor = auth()->user();
        return $this->apiResponseShow(new ProfileResource($vendor));
    }

    /**
     * Get a summary of the vendor's profile for dashboard navbar/sidebar display
     */
    public function showProfileSummary(): JsonResponse
    {
        $userId = auth()->id();
    
        $vendor = Cache::rememberForever("vendor_summary_{$userId}", function () use ($userId) {
            return DB::table('users')
                ->where('id', $userId)
                ->select('name')
                ->first();
        });

        return $this->apiResponseShow($vendor);
    }

    public function update(UpdateVendorProfileRequest $request): JsonResponse
    {
        $vendor = auth()->user();
        $data = $request->validated();
        $this->authService->logoutOtherDevicesOnPasswordChange($vendor, $data, $request);
        $vendor->update($data);
        $this->clearVendorSummaryCache($vendor->id);
        return $this->apiResponseUpdated(new ProfileResource($vendor));
    }
}