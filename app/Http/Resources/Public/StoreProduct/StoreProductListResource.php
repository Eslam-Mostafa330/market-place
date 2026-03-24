<?php

namespace App\Http\Resources\Public\StoreProduct;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreProductListResource extends JsonResource
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
            'name'        => $this->name,
            'slug'        => $this->slug,
            'price'       => $this->price,
            'sale_price'  => $this->sale_price,
            'image'       => $this->image_url,
            'is_favorite' => (bool) ($this->is_favorite ?? false),
        ];
    }
}