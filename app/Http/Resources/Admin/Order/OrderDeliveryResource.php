<?php

namespace App\Http\Resources\Admin\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderDeliveryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'phone'       => $this->delivery_phone,
            'address'     => $this->delivery_address_line,
            'city'        => $this->delivery_city,
            'state'       => $this->delivery_state,
            'country'     => $this->delivery_country,
            'postal_code' => $this->delivery_postal_code,
            'notes'       => $this->delivery_notes,
            'latitude'    => $this->delivery_latitude,
            'longitude'   => $this->delivery_longitude,
        ];
    }
}