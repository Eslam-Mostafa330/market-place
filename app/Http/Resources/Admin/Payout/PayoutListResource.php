<?php

namespace App\Http\Resources\Admin\Payout;

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
            'rider'   => new PayoutRiderResource($this->whenLoaded('rider')),
            'order'   => new PayoutOrderResource($this->whenLoaded('order')),
        ];
    }
}