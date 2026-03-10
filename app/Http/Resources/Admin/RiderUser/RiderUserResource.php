<?php

namespace App\Http\Resources\Admin\RiderUser;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RiderUserResource extends JsonResource
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
            'name'          => $this->name,
            'email'         => $this->email,
            'phone'         => $this->phone,
            'active_status' => $this->status,
        ];
    }
}
