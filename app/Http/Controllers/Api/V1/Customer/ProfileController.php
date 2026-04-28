<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Customer\Profile\UpdateCustomerProfileRequest;
use App\Http\Resources\Customer\Profile\ProfileResource;
use App\Models\User;
use App\Services\Auth\AuthService;
use App\Traits\ClearsCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class ProfileController extends BaseApiController
{
    use ClearsCache;

    public function __construct(private readonly AuthService $authService) {}

    public function show(): JsonResponse
    {
        $customer = auth()->user();
        $customer->load('customerProfile:user_id,date_of_birth,preferences,wallet_balance,loyalty_points');
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

        DB::transaction(function () use ($customer, $data, $request) {
            $this->authService->logoutOtherDevicesOnPasswordChange($customer, $data, $request);
            $customer->update(Arr::except($data, ['date_of_birth']));
            $this->updateCustomerProfile($customer, $data);
        });

        $customer->wasChanged('name') && $this->clearCustomerSummaryCache($customer->id);
        $customer->load('customerProfile:user_id,date_of_birth,preferences,wallet_balance,loyalty_points');
        return $this->apiResponseUpdated(new ProfileResource($customer));
    }

    /**
     * Update or create the authenticated customer's profile with provided data.
     * 
     * Handles only date_of_birth updates. If the field is present.
     */
    private function updateCustomerProfile(User $customer, array $data): void
    {
        if (! array_key_exists('date_of_birth', $data)) {
            return;
        }
        
        $customer->customerProfile()->updateOrCreate(
            ['user_id' => $customer->id],
            ['date_of_birth' => $data['date_of_birth']]
        );
    }
}