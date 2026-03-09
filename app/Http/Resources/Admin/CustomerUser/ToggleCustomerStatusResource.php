<?php

namespace App\Http\Resources\Admin\CustomerUser;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ToggleCustomerStatusResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->id,
            'status' => $this->status,
        ];
    }
}
