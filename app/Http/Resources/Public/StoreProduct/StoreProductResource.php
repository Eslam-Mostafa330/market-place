<?php

namespace App\Http\Resources\Public\StoreProduct;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Ramsey\Collection\Collection;

class StoreProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'price'            => $this->price,
            'sale_price'       => $this->sale_price,
            'image'            => $this->image_url,
            'quantity'         => $this->quantity,
            'preparation_time' => $this->preparation_time,
            'is_featured'      => $this->is_featured,
            'is_favorite'      => (bool) ($this->is_favorite ?? false),
            'description'      => $this->description,
            'related_products' => StoreProductListResource::collection($this->whenLoaded('relatedProducts')),
        ];
    }
}