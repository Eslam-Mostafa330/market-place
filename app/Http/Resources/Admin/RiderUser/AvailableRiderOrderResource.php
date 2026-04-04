<?php

namespace App\Http\Resources\Admin\RiderUser;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AvailableRiderOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'email'             => $this->email,
            'phone'             => $this->phone,
            'current_latitude'  => $this->riderProfile?->current_latitude,
            'current_longitude' => $this->riderProfile?->current_longitude,
        ];
    }
}