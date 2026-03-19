<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Customer\Profile\UpdateCustomerProfileRequest;
use App\Http\Resources\Customer\Profile\ProfileResource;
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
        $customer = auth()->user();
        return $this->apiResponseShow(new ProfileResource($customer));
    }

    /**
     * Get a summary of the customer's profile for dashboard navbar/sidebar display
     */
    public function showProfileSummary(): JsonResponse
    {
        $userId = auth()->id();
    
        $customer = Cache::rememberForever("customer_summary_{$userId}", function () use ($userId) {
            return DB::table('users')
                ->where('id', $userId)
                ->select('name')
                ->first();
        });

        return $this->apiResponseShow($customer);
    }

    public function update(UpdateCustomerProfileRequest $request): JsonResponse
    {
        $customer = auth()->user();
        $data = $request->validated();
        $this->authService->logoutOtherDevicesOnPasswordChange($customer, $data, $request);
        $customer->update($data);
        $this->clearCustomerSummaryCache($customer->id);
        return $this->apiResponseUpdated(new ProfileResource($customer));
    }
}