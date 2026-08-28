<?php

namespace App\Http\Resources\Customer\Support;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Messages are labelled by side rather than by sender id, which is all a
     * chat bubble needs and keeps staff identifiers out of the customer app.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'body'        => $this->body,
            'sender_type' => $this->sender_id === $request->user()?->id ? 'customer' : 'support',
            'read_at'     => $this->read_at,
            'created_at'  => $this->created_at,
        ];
    }
}
