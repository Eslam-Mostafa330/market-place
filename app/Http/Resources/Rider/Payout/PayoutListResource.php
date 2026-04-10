<?php

namespace App\Http\Resources\Rider\Payout;

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
            'id'            => $this->id,
            'amount'        => $this->amount,
            'status'        => $this->status,
            'payout_method' => $this->payout_method,
            'paid_at'       => $this->paid_at,
            'order'         => new PayoutOrderResource($this->whenLoaded('order')),
        ];
    }
}