<?php

namespace App\Http\Controllers\Api\V1\Rider;

use App\Enums\RiderAvailability;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Rider\Location\UpdateAvailabilityRequest;
use App\Http\Requests\Rider\Location\UpdateLocationRequest;
use App\Http\Resources\Rider\Location\LocationResource;
use App\Http\Resources\Rider\Location\RiderAvailabilityResource;
use App\Models\RiderProfile;
use App\Services\RiderLocationService;
use Illuminate\Http\Request;

class RiderLocationController extends BaseApiController
{
    public function __construct(private readonly RiderLocationService $locationService) {}

    public function getAvailability()
    {
        $profile = RiderProfile::where('user_id', auth()->id())->firstOrFail();

        return $this->apiResponse(new RiderAvailabilityResource($profile));
    }

    /**
     * Updates the authenticated rider's current GPS coordinates.
     * Rejects the request immediately if the rider is not marked as available,
     *
     * @param Request $request  Must contain: latitude (numeric), longitude (numeric)
     */
    public function updateLocation(UpdateLocationRequest $request)
    {
        $profile = RiderProfile::where('user_id', auth()->id())->firstOrFail();
        abort_if($profile->rider_availability !== RiderAvailability::AVAILABLE, 422, 'Cannot update location while unavailable');

        $data = $request->validated();
        $now  = $this->locationService->updateRiderLocation($profile, $data['current_latitude'], $data['current_longitude']);

        $profile->fill([
            'current_latitude'         => $data['current_latitude'],
            'current_longitude'        => $data['current_longitude'],
            'last_location_updated_at' => $now,
        ]);

        return $this->apiResponseUpdated(new LocationResource($profile));
    }

    /**
     * Updates the authenticated rider's availability status.
     *
     * When a rider goes unavailable, their Redis location keys are removed
     * immediately so they stop appearing in nearest-rider lookups.
     * When they go available, their keys will be recreated on the next location update.
     *
     * @param Request $request  Must contain: availability (int, 1 = available, 2 = unavailable)
     */
    public function updateAvailability(UpdateAvailabilityRequest $request)
    {
        $profile = RiderProfile::where('user_id', auth()->id())->firstOrFail();
        $newAvailability = RiderAvailability::from((int) $request->validated('rider_availability'));
        $profile->update(['rider_availability' => $newAvailability]);

        if ($newAvailability === RiderAvailability::UNAVAILABLE) {
            $this->locationService->removeRiderLocation($profile->id);
        }

        return $this->apiResponseUpdated(new RiderAvailabilityResource($profile));
    }
}