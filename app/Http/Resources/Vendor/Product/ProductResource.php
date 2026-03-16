<?php

namespace App\Http\Resources\Vendor\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'slug'             => $this->slug,
            'description'      => $this->description,
            'image'            => $this->image_url,
            'price'            => $this->price,
            'sale_price'       => $this->sale_price,
            'quantity'         => $this->quantity,
            'preparation_time' => $this->preparation_time,
            'is_featured'      => $this->is_featured,
            'active_status'    => $this->status,
            'created_at'       => $this->created_at->format('d-m-Y'),
            'product_category' => $this->whenLoaded('productCategory', fn () => $this->productCategory?->name),
        ];
    }
}