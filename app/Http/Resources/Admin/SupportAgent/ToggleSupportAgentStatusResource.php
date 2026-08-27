<?php

namespace App\Http\Resources\Admin\SupportAgent;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ToggleSupportAgentStatusResource extends JsonResource
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
