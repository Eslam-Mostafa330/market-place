<?php

namespace App\Http\Resources\Vendor\Store;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'description'       => $this->description,
            'logo'              => $this->logo_url,
            'image'             => $this->image_url,
            'business_category' => new BusinessCategoryResource($this->whenLoaded('businessCategory')),
        ];
    }
}
