<?php

namespace App\Http\Resources\Support\Ticket;

use App\Traits\IncludesAttributes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
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
            'id'               => $this->id,
            'subject'          => $this->subject,
            'category'         => $this->category,
            'status'           => $this->status,
            'order_id'         => $this->order_id,
            'requester'        => new RequesterResource($this->whenLoaded('requester')),
            'agent'            => new AgentResource($this->whenLoaded('agent')),
            'unread_count'     => $this->whenExists($this->unread_count),
            'last_message_at'  => $this->last_message_at,
            'first_replied_at' => $this->first_replied_at,
            'closed_at'        => $this->closed_at,
            'created_at'       => $this->created_at,
            'messages'         => MessageResource::collection($this->whenLoaded('messages')),
        ];
    }
}
