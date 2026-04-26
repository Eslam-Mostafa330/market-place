<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\UserRole;
use App\Enums\VendorVerificationStatus;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Api\V1\Admin\Concerns\AdminAuthorization;
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
    use AdminAuthorization;

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
        $this->authorizeVendorAction($vendor);
        $data = $request->validated();
        $vendor->update($data);
        return $this->apiResponseUpdated(new VendorUserResource($vendor));
    }

    public function destroy(User $vendor): JsonResponse
    {
        $this->authorizeVendorAction($vendor);
        abort_if($vendor->store()->exists(), 422, __('vendors.cannot_delete_due_store'));
        abort_if($vendor->vendorPayouts()->exists(), 422, __('vendors.cannot_delete_due_payout'));
        
        $vendor->delete();
        return $this->apiResponseDeleted();
    }

    /**
     * Toggle the status of a vendor.
     */
    public function toggleStatus(User $vendor): JsonResponse
    {
        $this->authorizeVendorAction($vendor);
        $this->userStatusService->toggle($vendor);
        return $this->apiResponseUpdated(new ToggleVendorStatusResource($vendor));
    }
}