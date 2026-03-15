<?php

namespace App\Http\Resources\Admin\StoreBranch;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreBranchListResource extends JsonResource
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
            'city'          => $this->city,
            'phone'         => $this->phone,
            'active_status' => $this->status,
            'created_at'    => $this->created_at->format('d-m-Y'),
        ];
    }
}
