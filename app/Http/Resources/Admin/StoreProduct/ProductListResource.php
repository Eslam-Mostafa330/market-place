<?php

namespace App\Http\Resources\Admin\StoreProduct;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResource extends JsonResource
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
            'image'            => $this->image_url,
            'price'            => $this->price,
            'quantity'         => $this->quantity,
            'active_status'    => $this->status,
            'product_category' => $this->whenLoaded('productCategory', fn () => $this->productCategory?->name),
        ];
    }
}
