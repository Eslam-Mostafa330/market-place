<?php

namespace App\Http\Resources\Support\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'product_name' => $this->product_name,
            'quantity'     => $this->quantity,
            'unit_price'   => $this->unit_price,
            'subtotal'     => $this->subtotal,
        ];
    }
}
