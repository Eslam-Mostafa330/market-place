<?php

namespace App\Http\Resources\Customer\Address;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'country'          => $this->country,
            'city'             => $this->city,
            'state'            => $this->state,
            'postal_code'      => $this->postal_code,
            'address_line_1'   => $this->address_line_1,
            'address_line_2'   => $this->address_line_2,
            'contact_phone'    => $this->contact_phone,
            'additional_phone' => $this->additional_phone,
            'additional_info'  => $this->additional_info,
            'latitude'         => $this->latitude,
            'longitude'        => $this->longitude,
            'is_default'       => $this->is_default,
            'type'             => $this->type,
        ];
    }
}