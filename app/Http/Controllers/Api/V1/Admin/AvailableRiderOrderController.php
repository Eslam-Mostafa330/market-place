<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Admin\RiderUser\AvailableRiderOrderResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AvailableRiderOrderController extends BaseApiController
{
    public function __invoke(): AnonymousResourceCollection
    {
        $availableRiders = User::select('id', 'name', 'email', 'phone')
            ->with('riderProfile:id,user_id,current_latitude,current_longitude')
            ->availableRiders()
            ->useFilters()
            ->latest()
            ->dynamicPaginate();

        return AvailableRiderOrderResource::collection($availableRiders);
    }
}