<?php

namespace App\Http\Resources\Admin\VendorUser;

use App\Enums\VendorVerificationStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'business_name'        => $this->business_name,
            'business_email'       => $this->business_email ?? $this->user?->email,
            'business_phone'       => $this->business_phone ?? $this->user?->phone,
            'business_license'     => $this->business_license,
            'business_description' => $this->business_description,
            'rating'               => $this->rating,
            'total_orders'         => $this->total_orders,
            'verification_status'  => $this->verification_status,
            'rejection_reason'     => $this->when($this->verification_status === VendorVerificationStatus::REJECTED, $this->rejection_reason),
        ];
    }
}
