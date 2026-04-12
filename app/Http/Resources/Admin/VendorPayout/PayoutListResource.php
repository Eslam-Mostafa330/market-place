<?php

namespace App\Http\Resources\Admin\VendorPayout;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayoutListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'      => $this->id,
            'amount'  => $this->amount,
            'status'  => $this->status,
            'paid_at' => $this->paid_at,
            'vendor'  => new PayoutVendorResource($this->whenLoaded('vendor')),
            'order'   => new PayoutOrderResource($this->whenLoaded('order')),
        ];
    }
}
