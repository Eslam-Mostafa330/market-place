<?php

namespace App\Http\Resources\Vendor\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreOverviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'average_rating' => $this->average_rating,
            'reviews_count'  => $this->reviews_count,
        ];
    }
}
