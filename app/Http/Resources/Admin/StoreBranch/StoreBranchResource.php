<?php

namespace App\Http\Resources\Admin\StoreBranch;

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
            'address'           => $this->address,
            'city'              => $this->city,
            'area'              => $this->area,
            'phone'             => $this->phone,
            'delivery_fee'      => $this->delivery_fee,
            'delivery_time_max' => $this->delivery_time_max,
            'latitude'          => $this->latitude,
            'longitude'         => $this->longitude,
            'active_status'     => $this->status,
            'created_at'        => $this->created_at->format('d-m-Y'),
        ];
    }
}
