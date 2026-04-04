<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\DefineStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Admin\RiderUser\CreateRiderRequest;
use App\Http\Requests\Admin\RiderUser\UpdateRiderRequest;
use App\Http\Resources\Admin\RiderUser\RiderUserListResource;
use App\Http\Resources\Admin\RiderUser\RiderUserResource;
use App\Http\Resources\Admin\RiderUser\ToggleRiderStatusResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

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

    public function show(User $rider): JsonResponse
    {
        $rider->load('riderProfile:id,user_id,license_number,license_expiry,vehicle_type,vehicle_number,total_deliveries');
        return $this->apiResponse(new RiderUserResource($rider));
    }

    public function store(CreateRiderRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            $user    = User::create($this->getUserData($data));
            $profile = $user->riderProfile()->create($this->getProfileData($data));

            return $user->setRelation('riderProfile', $profile);
        });

        return $this->apiResponseStored(new RiderUserResource($user));
    }

    public function update(UpdateRiderRequest $request, User $rider): JsonResponse
    {
        abort_unless($rider->isRider(), 422, __('validation.custom.verify_riders'));
        $data = $request->validated();

        $rider = DB::transaction(function () use ($data, $rider) {
            $rider->update($this->getUserData($data));

            $profile = $rider->riderProfile()->updateOrCreate(
                [],
                $this->getProfileData($data)
            );

            return $rider->setRelation('riderProfile', $profile);
        });

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

    /**
     * Extract and prepare user data for creating or updating a rider.
     */
    private function getUserData(array $data): array
    {
        return [
            ...Arr::only($data, ['name', 'email', 'phone', 'password']),
            'role' => UserRole::RIDER,
        ];
    }

    /**
     * Extract and prepare rider profile data for creating or updating a rider profile.
     */
    private function getProfileData(array $data): array
    {
        return Arr::only($data, ['license_number', 'license_expiry', 'vehicle_type', 'vehicle_number']);
    }
}