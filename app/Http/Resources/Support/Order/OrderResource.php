<?php

namespace App\Http\Resources\Support\Order;

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
            'order_number'   => $this->order_number,
            'order_status'   => $this->order_status,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'subtotal'       => $this->subtotal,
            'delivery_fee'   => $this->delivery_fee,
            'discount'       => $this->discount,
            'total'          => $this->total,
            'placed_at'      => $this->created_at,
            'delivered_at'   => $this->delivered_at,
            'delivery_city'  => $this->delivery_city,
            'delivery_phone' => $this->delivery_phone,
            'items'          => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
