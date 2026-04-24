<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\UserRole;
use App\Enums\VendorVerificationStatus;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Admin\VendorUser\CreateVendorRequest;
use App\Http\Requests\Admin\VendorUser\UpdateVendorRequest;
use App\Http\Resources\Admin\VendorUser\ToggleVendorStatusResource;
use App\Http\Resources\Admin\VendorUser\VendorUserResource;
use App\Models\User;
use App\Services\UserStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VendorController extends BaseApiController
{
    public function __construct(private readonly UserStatusService $userStatusService) {}

    public function index(): AnonymousResourceCollection
    {
        $vendors = User::select('users.id', 'users.name', 'users.email', 'users.phone', 'users.status', 'vendor_profiles.verification_status')
            ->vendor()
            ->leftJoin('vendor_profiles', 'vendor_profiles.user_id', '=', 'users.id')
            ->orderByRaw('verification_status = ? DESC', [VendorVerificationStatus::PENDING->value])
            ->useFilters()
            ->latest('users.created_at')
            ->dynamicPaginate();

        return VendorUserResource::collection($vendors);
    }

    public function store(CreateVendorRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['role'] = UserRole::VENDOR;
        $user = User::create($data);
        return $this->apiResponseStored(new VendorUserResource($user));
    }

    public function update(UpdateVendorRequest $request, User $vendor): JsonResponse
    {
        abort_unless($vendor->isVendor(), 422, __('validation.custom.verify_vendors'));
        $data = $request->validated();
        $vendor->update($data);
        return $this->apiResponseUpdated(new VendorUserResource($vendor));
    }

    public function destroy(User $vendor): JsonResponse
    {
        abort_unless($vendor->isVendor(), 422, __('validation.custom.verify_vendors'));
        $vendor->delete();
        return $this->apiResponseDeleted();
    }

    /**
     * Toggle the status of a vendor.
     */
    public function toggleStatus(User $vendor): JsonResponse
    {
        abort_unless($vendor->isVendor(), 422, __('validation.custom.verify_vendors'));
        $this->userStatusService->toggle($vendor);
        return $this->apiResponseUpdated(new ToggleVendorStatusResource($vendor));
    }
}