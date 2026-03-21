<?php

namespace App\Http\Resources\Customer\Address;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerAddressListResource extends JsonResource
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
            'country'        => $this->country,
            'city'           => $this->city,
            'state'          => $this->state,
            'address_line_1' => $this->address_line_1,
            'is_default'     => $this->is_default,
        ];
    }
}