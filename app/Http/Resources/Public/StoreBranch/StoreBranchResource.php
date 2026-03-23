<?php

namespace App\Http\Resources\Public\StoreBranch;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreBranchResource extends JsonResource
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
            'slug'              => $this->slug,
            'city'              => $this->city,
            'area'              => $this->area,
            'address'           => $this->address,
            'phone'             => $this->phone,
            'latitude'          => $this->latitude,
            'longitude'         => $this->longitude,
            'longitude'         => $this->longitude,
            'delivery_time_max' => $this->delivery_time_max,
            'delivery_fee'      => $this->delivery_fee,
        ];
    }
}