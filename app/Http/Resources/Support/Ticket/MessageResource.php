<?php

namespace App\Http\Resources\Support\Ticket;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'body'        => $this->body,
            'sender_name' => $this->whenLoaded('sender', fn () => $this->sender->name),
            'from_desk'   => $this->whenLoaded('sender', fn () => $this->sender->staffsSupportDesk()),
            'read_at'     => $this->read_at,
            'created_at'  => $this->created_at,
        ];
    }
}
