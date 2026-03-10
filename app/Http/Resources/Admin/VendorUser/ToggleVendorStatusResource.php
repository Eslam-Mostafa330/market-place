<?php

namespace App\Http\Resources\Admin\VendorUser;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ToggleVendorStatusResource extends JsonResource
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
            'active_status' => $this->status,
        ];
    }
}
