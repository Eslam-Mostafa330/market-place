<?php

namespace App\Http\Resources\Admin\BusinessCategory;

use App\Traits\IncludesAttributes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessCategoryResource extends JsonResource
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
            'id'           => $this->id,
            'name'         => $this->name,
            'image'        => $this->whenExists($this->image_url),
            'description'  => $this->whenExists($this->description),
            'stores_count' => $this->whenExists($this->stores_count),
        ];
    }
}
