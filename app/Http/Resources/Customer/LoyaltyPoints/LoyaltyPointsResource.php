<?php

namespace App\Http\Resources\Customer\LoyaltyPoints;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoyaltyPointsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'loyalty_points' => $this->loyalty_points,
            'wallet_balance' => $this->wallet_balance,
        ];
    }
}
