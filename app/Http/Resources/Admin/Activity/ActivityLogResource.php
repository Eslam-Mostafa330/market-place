<?php

namespace App\Http\Resources\Admin\Activity;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'event'        => $this->event,
            'subject_type' => class_basename($this->subject_type),
            'subject_id'   => $this->subject_id,
            'causer'       => $this->causer?->name,
            'old'          => $this->properties['old'] ?? null,
            'new'          => $this->properties['attributes'] ?? null,
            'performed_at' => $this->created_at,
        ];
    }
}
