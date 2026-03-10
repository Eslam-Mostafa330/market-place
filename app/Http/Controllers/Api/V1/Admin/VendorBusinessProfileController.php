<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\VendorVerificationStatus;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Admin\VendorUser\UpdateVendorVerificationRequest;
use App\Http\Resources\Admin\VendorUser\BusinessProfileResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class VendorBusinessProfileController extends BaseApiController
{
    public function show(User $vendor): JsonResponse
    {
        $vendorProfile = $vendor->vendorProfile;
        $vendorProfile->setRelation('user', $vendor);
        return $this->apiResponseShow(new BusinessProfileResource($vendorProfile));
    }

    /**
     * Update the verification status of a vendor's business profile. and set a rejection reason if the status is rejected
     */
    public function update(UpdateVendorVerificationRequest $request, User $vendor): JsonResponse
    {
        $profile = $vendor->vendorProfile;
        abort_if(! $profile, 404);
        $status = VendorVerificationStatus::from($request->verification_status);

        $profile->update([
            'verification_status' => $status->value,
            'rejection_reason'    => $status === VendorVerificationStatus::REJECTED ? $request->rejection_reason : null,
        ]);

        $profile->setRelation('user', $vendor);
        return $this->apiResponseUpdated(new BusinessProfileResource($profile));
    }
}