<?php

namespace App\Http\Resources\Vendor\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LatestReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'rate'        => $this->rate,
            'full_review' => $this->full_review,
            'reviewed_at' => $this->reviewed_at,
            'store_id'    => $this->store_id,
            'store_name'  => $this->store_name,
            'customer'    => $this->customer,
        ];
    }
}
