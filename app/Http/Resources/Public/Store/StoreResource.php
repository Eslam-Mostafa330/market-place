<?php

namespace App\Http\Resources\Public\Store;

use App\Traits\IncludesAttributes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreResource extends JsonResource
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
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'logo'        => $this->logo_url,
            'image'       => $this->whenExists($this->image),
            'description' => $this->whenExists($this->description),
            'vendor'      => new StoreVendorResource($this->whenLoaded('vendorProfile'))
        ];
    }
}
