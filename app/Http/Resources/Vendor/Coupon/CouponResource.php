<?php

namespace App\Http\Resources\Vendor\Coupon;

use App\Http\Resources\Vendor\Store\StoreListResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'name'                 => $this->name,
            'code'                 => $this->code,
            'description'          => $this->description,
            'minimum_order'        => $this->minimum_order,
            'maximum_discount'     => $this->maximum_discount,
            'coupon_type'          => $this->coupon_type,
            'value'                => $this->value,
            'usage_limit_per_user' => $this->usage_limit_per_user,
            'starts_at'            => $this->starts_at,
            'expires_at'           => $this->expires_at,
            'status'               => $this->status,
        ];
    }
}