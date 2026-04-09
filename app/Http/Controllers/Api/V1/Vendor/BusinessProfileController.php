<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Enums\VendorVerificationStatus;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Vendor\Profile\UpdateVendorBusinessProfileRequest;
use App\Http\Resources\Vendor\BusinessProfile\BusinessProfileResource;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Http\JsonResponse;

class BusinessProfileController extends BaseApiController
{
    public function show(): JsonResponse
    {
        $vendor = auth()->user();
        $vendorProfile = $vendor->vendorProfile;
        
        if (! $vendorProfile) {
            return $this->handleIncompleteBusinessProfile();
        }
        
        $vendorProfile?->setRelation('user', $vendor);
        return $this->apiResponseShow(new BusinessProfileResource($vendorProfile));
    }

    /**
     * Update the vendor's business profile. If the vendor does not have a business profile yet, it will be created. If the vendor already has a business profile, it will be updated. If there are changes to the existing business profile, the verification status will be set to PENDING and the rejection reason will be cleared.
     */
    public function update(UpdateVendorBusinessProfileRequest $request): JsonResponse
    {
        $vendor = auth()->user();
        $vendorProfile = $vendor->vendorProfile;
        $data = $request->validated();

        $vendorProfile = $vendorProfile
            ? $this->updateBusinessProfile($vendorProfile, $data)
            : $this->createBusinessProfile($vendor, $data);

        $vendorProfile->setRelation('user', $vendor);
        return $this->apiResponseUpdated(new BusinessProfileResource($vendorProfile));
    }

    /**
     * Create a new business profile for the vendor with the provided data. The verification status will be set to PENDING by default.
     */
    private function createBusinessProfile(User $vendor, array $data): VendorProfile
    {
        return $vendor->vendorProfile()->create([
            ...$data,
            'verification_status' => VendorVerificationStatus::PENDING->value,
        ]);
    }

    /**
     * Update the existing business profile with the new data. If there are changes, set the verification status to PENDING and clear the rejection reason.
     */
    private function updateBusinessProfile(VendorProfile $vendorProfile, array $data): VendorProfile
    {
        $vendorProfile->fill($data);

        if ($vendorProfile->isDirty()) {
            $vendorProfile->update([
                ...$data,
                'verification_status' => VendorVerificationStatus::PENDING->value,
                'rejection_reason'    => null,
            ]);
        }

        return $vendorProfile;
    }

    /**
     * Handle the case when the vendor does not have a business profile yet. This can be used to prompt the vendor to complete their business profile and get verified.
     */
    private function handleIncompleteBusinessProfile(): JsonResponse
    {
        return $this->apiResponseShow([
            'verification_status' => VendorVerificationStatus::INCOMPLETE->value,
            'message'             => __('vendors.vendor_profile_incomplete'),
        ]);
    }
}