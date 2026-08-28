<?php

namespace App\Http\Resources\Support\Ticket;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentStatusResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'availability' => $this->availability,
            'last_seen_at' => $this->last_seen_at,
        ];
    }
}
