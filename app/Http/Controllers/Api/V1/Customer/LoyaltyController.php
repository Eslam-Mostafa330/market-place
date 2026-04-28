<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Customer\LoyaltyPoints\RedeemPointsRequest;
use App\Http\Resources\Customer\LoyaltyPoints\LoyaltyPointsResource;
use App\Models\CustomerProfile;
use App\Services\Customer\LoyaltyService;

class LoyaltyController extends BaseApiController
{
    public function __construct(private readonly LoyaltyService $loyaltyService) {}

    /**
     * Redeem loyalty points as wallet balance.
     */
    public function __invoke(RedeemPointsRequest $request)
    {
        $data = $request->validated();

        $profile = CustomerProfile::select('id', 'loyalty_points', 'wallet_balance')
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $profile = $this->loyaltyService->redeemPoints($profile, $data['points']);
        return $this->apiResponseStored(new LoyaltyPointsResource($profile));
    }
}