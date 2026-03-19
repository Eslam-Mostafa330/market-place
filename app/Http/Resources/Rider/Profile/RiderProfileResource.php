<?php

namespace App\Http\Resources\Rider\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RiderProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'license_number'   => $this->license_number,
            'license_expiry'   => $this->license_expiry,
            'vehicle_type'     => $this->vehicle_type,
            'vehicle_number'   => $this->vehicle_number,
            'total_deliveries' => $this->total_deliveries,
        ];
    }
}