<?php

namespace App\Http\Resources\Vendor\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderListResource extends JsonResource
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
            'order_number'   => $this->order_number,
            'order_status'   => $this->order_status,
            'payment_status' => $this->payment_status,
            'total'          => $this->total,
            'created_at'     => $this->created_at->format('d-m-Y'),
            'store'          => new OrderStoreResource($this->whenLoaded('store')),
            'store_branch'   => new OrderStoreBranchResource($this->whenLoaded('storeBranch')),
        ];
    }
}
