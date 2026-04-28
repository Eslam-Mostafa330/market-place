<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Api\V1\Admin\Concerns\AdminAuthorization;
use App\Http\Requests\Admin\RiderUser\CreateRiderRequest;
use App\Http\Requests\Admin\RiderUser\UpdateRiderRequest;
use App\Http\Resources\Admin\RiderUser\RiderUserResource;
use App\Http\Resources\Admin\RiderUser\ToggleRiderStatusResource;
use App\Models\User;
use App\Services\Rider\RiderService;
use App\Services\UserStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RiderController extends BaseApiController
{
    use AdminAuthorization;

    public function __construct(private readonly UserStatusService $userStatusService, private readonly RiderService $riderService) {}

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
        $user = $this->riderService->createRider($request->validated());

        return $this->apiResponseStored(new RiderUserResource($user));
    }

    public function update(UpdateRiderRequest $request, User $rider): JsonResponse
    {
        $this->authorizeRiderAction($rider);

        $rider = $this->riderService->updateRider($rider, $request->validated());

        return $this->apiResponseUpdated(new RiderUserResource($rider));
    }

    public function destroy(User $rider): JsonResponse
    {
        $this->authorizeRiderAction($rider);
        abort_if($rider->riderPayouts()->exists(), 422, __('riders.cannot_delete_due_payouts'));
        $rider->delete();
        return $this->apiResponseDeleted();
    }

    /**
     * Toggle the status of a rider.
     */
    public function toggleStatus(User $rider): JsonResponse
    {
        $this->authorizeRiderAction($rider);
        $this->userStatusService->toggle($rider);
        return $this->apiResponseUpdated(new ToggleRiderStatusResource($rider));
    }
}