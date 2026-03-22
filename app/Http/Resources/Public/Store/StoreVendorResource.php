<?php

namespace App\Http\Resources\Public\Store;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreVendorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name'         => $this->business_name,
            'rating'       => $this->rating,
            'total_orders' => $this->total_orders,
            'joined_at'    => $this->created_at->format('d-m-Y')
        ];
    }
}