<?php

namespace App\Http\Resources\Customer\Support;

use App\Traits\IncludesAttributes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    use IncludesAttributes;

    /**
     * Transform the resource into an array.
     *
     * The agent is named only once one is handling the ticket, and never more
     * than their name: the customer is talking to a person, not browsing staff.
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
            'order_id'        => $this->order_id,
            'agent_name'      => $this->whenLoaded('agent', fn () => $this->agent?->name),
            'unread_count'    => $this->whenExists($this->unread_count),
            'last_message_at' => $this->last_message_at,
            'created_at'      => $this->created_at,
            'messages'        => MessageResource::collection($this->whenLoaded('messages')),
        ];
    }
}
