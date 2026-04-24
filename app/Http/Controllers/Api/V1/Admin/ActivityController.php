<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Admin\Activity\ActivityLogResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\Activitylog\Models\Activity;

class ActivityController extends BaseApiController
{
    public function __invoke(): AnonymousResourceCollection
    {
        $activities = Activity::select('id', 'subject_type', 'subject_id', 'causer_type', 'causer_id', 'properties', 'event', 'created_at')
            ->with('causer:id,name')
            ->latest()
            ->dynamicPaginate();

        return ActivityLogResource::collection($activities);
    }
}