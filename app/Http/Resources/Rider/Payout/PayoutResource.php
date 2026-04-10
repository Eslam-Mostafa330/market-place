<?php

namespace App\Http\Resources\Rider\Payout;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayoutResource extends JsonResource
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
            'reference'     => $this->reference,
            'notes'         => $this->notes,
            'payout_proof'  => $this->payout_proof_url,
            'paid_at'       => $this->paid_at,
            'order'         => new PayoutOrderResource($this->whenLoaded('order')),
        ];
    }
}