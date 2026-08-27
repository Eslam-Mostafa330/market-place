<?php

namespace App\Http\Resources\Admin\SupportAgent;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportAgentResource extends JsonResource
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
            'availability'  => $this->whenLoaded('agentStatus', fn () => $this->agentStatus?->availability),
        ];
    }
}
