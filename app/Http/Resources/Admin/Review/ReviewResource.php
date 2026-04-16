<?php

namespace App\Http\Resources\Admin\Review;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
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
            'created_at'  => $this->created_at,
            'customer'    => new ReviewCustomerResource($this->whenLoaded('customer')),
            'store'       => new ReviewStoreResource($this->whenLoaded('store')),
        ];
    }
}
