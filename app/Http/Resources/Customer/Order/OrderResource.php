<?php

namespace App\Http\Resources\Customer\Order;

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
            'id'                => $this->id,
            'order_number'      => $this->order_number,
            'order_status'      => $this->order_status,
            'payment_status'    => $this->payment_status,
            'payment_method'    => $this->payment_method,
            'notes'             => $this->notes,
            'subtotal'          => $this->subtotal,
            'delivery_fee'      => $this->delivery_fee,
            'discount'          => $this->discount,
            'wallet_discount'   => $this->wallet_discount,
            'total'             => $this->total,
            'store_name'        => $this->store->name,
            'store_branch_name' => $this->storeBranch->name,
            'created_at'        => $this->created_at,
            'delivery'          => new OrderDeliveryResource($this->whenLoaded('delivery')),
            'items'             => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}