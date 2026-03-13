<?php

namespace App\Http\Resources\Rider\Location;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'current_latitude'  => $this->current_latitude,
            'current_longitude' => $this->current_longitude,
            'updated_at'        => $this->last_location_updated_at->toISOString(),
        ];
    }
}
