<?php

namespace App\Http\Resources\Customer\Support;

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
            'from_desk'   => $this->sender_id !== $request->user()?->id,
            'read_at'     => $this->read_at,
            'created_at'  => $this->created_at,
        ];
    }
}
