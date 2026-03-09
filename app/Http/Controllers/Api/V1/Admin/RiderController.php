<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\DefineStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Admin\RiderUser\CreateRiderRequest;
use App\Http\Requests\Admin\RiderUser\UpdateRiderRequest;
use App\Http\Resources\Admin\RiderUser\RiderUserResource;
use App\Http\Resources\Admin\RiderUser\ToggleRiderStatusResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RiderController extends BaseApiController
{
    public function index(): AnonymousResourceCollection
    {
        $riders = User::select('id', 'name', 'email', 'phone', 'status')
            ->rider()
            ->useFilters()
            ->latest()
            ->dynamicPaginate();

        return RiderUserResource::collection($riders);
    }

    public function store(CreateRiderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['role'] = UserRole::RIDER;
        $user = User::create($data);
        return $this->apiResponseStored(new RiderUserResource($user));
    }

    public function update(UpdateRiderRequest $request, User $rider): JsonResponse
    {
        abort_unless($rider->isRider(), 422, __('validation.custom.verify_riders'));
        $data = $request->validated();
        $rider->update($data);
        return $this->apiResponseUpdated(new RiderUserResource($rider));
    }

    public function destroy(User $rider): JsonResponse
    {
        abort_unless($rider->isRider(), 422, __('validation.custom.verify_riders'));
        $rider->delete();
        return $this->apiResponseDeleted();
    }

    /**
     * Toggle the status of a rider
     */
    public function toggleStatus(User $rider): JsonResponse
    {
        abort_unless($rider->isRider(), 422, __('validation.custom.verify_riders'));

        $newStatus = $rider->status === DefineStatus::ACTIVE
            ? DefineStatus::INACTIVE
            : DefineStatus::ACTIVE;

        $rider->update(['status' => $newStatus]);
        return $this->apiResponseUpdated(new ToggleRiderStatusResource($rider));
    }
}