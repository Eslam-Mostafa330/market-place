<?php

namespace App\Http\Resources\Vendor\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'payment_method' => $this->payment_method,
            'notes'          => $this->notes,
            'subtotal'       => $this->subtotal,
            'delivery_fee'   => $this->delivery_fee,
            'discount'       => $this->discount,
            'total'          => $this->total,
            'created_at'     => $this->created_at->format('d-m-y'),
            'store'          => new OrderStoreResource($this->whenLoaded('store')),
            'store_branch'   => new OrderStoreBranchResource($this->whenLoaded('storeBranch')),
            'delivery'       => new OrderDeliveryResource($this->whenLoaded('delivery')),
            'items'          => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}