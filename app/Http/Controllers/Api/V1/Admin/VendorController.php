<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\DefineStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Admin\VendorUser\CreateVendorRequest;
use App\Http\Requests\Admin\VendorUser\UpdateVendorRequest;
use App\Http\Resources\Admin\VendorUser\ToggleVendorStatusResource;
use App\Http\Resources\Admin\VendorUser\VendorUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VendorController extends BaseApiController
{
    public function index(): AnonymousResourceCollection
    {
        $vendors = User::select('id', 'name', 'email', 'phone', 'status')
            ->vendor()
            ->useFilters()
            ->latest()
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
     * Toggle the status of a vendor
     */
    public function toggleStatus(User $vendor): JsonResponse
    {
        abort_unless($vendor->isVendor(), 422, __('validation.custom.verify_vendors'));

        $newStatus = $vendor->status === DefineStatus::ACTIVE
            ? DefineStatus::INACTIVE
            : DefineStatus::ACTIVE;

        $vendor->update(['status' => $newStatus]);
        return $this->apiResponseUpdated(new ToggleVendorStatusResource($vendor));
    }
}
