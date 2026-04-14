<?php

namespace App\Http\Controllers\Api\V1\Rider;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Rider\Profile\UpdateRiderProfileRequest;
use App\Http\Resources\Rider\Profile\ProfileResource;
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
        $rider = auth()->user();
        $rider->load('riderProfile:id,user_id,license_number,license_expiry,vehicle_type,vehicle_number,total_deliveries');
        return $this->apiResponseShow(new ProfileResource($rider));
    }

    /**
     * Get a summary of the rider's profile for dashboard navbar/sidebar display
     */
    public function showProfileSummary(): JsonResponse
    {
        $userId = auth()->id();
    
        $rider = Cache::rememberForever("rider_summary_{$userId}", function () use ($userId) {
            return DB::table('users')
                ->where('id', $userId)
                ->select('name')
                ->first();
        });

        return $this->apiResponseShow($rider);
    }

    public function update(UpdateRiderProfileRequest $request): JsonResponse
    {
        $rider = auth()->user();
        $data = $request->validated();
        $this->authService->logoutOtherDevicesOnPasswordChange($rider, $data, $request);
        $rider->update($data);
        $rider->wasChanged('name') && $this->clearRiderSummaryCache($rider->id);
        return $this->apiResponseUpdated(new ProfileResource($rider));
    }
}