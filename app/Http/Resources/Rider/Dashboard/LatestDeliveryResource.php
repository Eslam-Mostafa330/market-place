<?php

namespace App\Http\Resources\Rider\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LatestDeliveryResource extends JsonResource
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
            'store_name'     => $this->store_name,
            'delivery_city'  => $this->delivery_city,
            'delivery_state' => $this->delivery_state,
            'earned'         => (float) $this->rider_earnings,
            'payment_method' => $this->payment_method,
            'delivered_at'   => $this->delivered_at,
        ];
    }
}
