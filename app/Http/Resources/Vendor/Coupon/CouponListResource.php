<?php

namespace App\Http\Resources\Vendor\Coupon;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponListResource extends JsonResource
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
            'code'        => $this->code,
            'coupon_type' => $this->coupon_type,
            'value'       => $this->value,
            'expires_at'  => $this->expires_at,
            'status'      => $this->status,
        ];
    }
}
