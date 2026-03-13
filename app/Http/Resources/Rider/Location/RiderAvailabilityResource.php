<?php

namespace App\Http\Resources\Rider\Location;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RiderAvailabilityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'availability' => $this->rider_availability,
        ];
    }
}
