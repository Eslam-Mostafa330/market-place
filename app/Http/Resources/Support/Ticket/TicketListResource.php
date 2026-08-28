<?php

namespace App\Http\Resources\Support\Ticket;

use App\Traits\IncludesAttributes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketListResource extends JsonResource
{
    use IncludesAttributes;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'subject'         => $this->subject,
            'category'        => $this->category,
            'status'          => $this->status,
            'agent_name'      => $this->whenLoaded('agent', fn () => $this->agent?->name),
            'is_mine'         => $this->agent_id === $request->user()->id,
            'unread_count'    => $this->whenExists($this->unread_count),
            'last_message_at' => $this->last_message_at,
        ];
    }
}
