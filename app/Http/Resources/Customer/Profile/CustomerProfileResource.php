<?php

namespace App\Http\Resources\Customer\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'date_of_birth'  => $this->date_of_birth,
            'wallet_balance' => $this->wallet_balance,
            'loyalty_points' => $this->loyalty_points,
            'preferences'    => $this->preferences,
        ];
    }
}