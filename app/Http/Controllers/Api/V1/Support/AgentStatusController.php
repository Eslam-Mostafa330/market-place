<?php

namespace App\Http\Controllers\Api\V1\Support;

use App\Enums\AgentAvailability;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Support\Availability\UpdateAvailabilityRequest;
use App\Http\Resources\Support\Ticket\AgentStatusResource;
use App\Services\Support\SupportPresenceService;
use Illuminate\Http\JsonResponse;

class AgentStatusController extends BaseApiController
{
    public function __construct(private readonly SupportPresenceService $supportPresenceService) {}

    /**
     * Return the agent's current presence status.
     */
    public function show(): JsonResponse
    {
        $status = $this->supportPresenceService->statusFor(auth()->user());

        return $this->apiResponseShow(new AgentStatusResource($status));
    }

    public function update(UpdateAvailabilityRequest $request): JsonResponse
    {
        $status = $this->supportPresenceService->heartbeat(
            agent: $request->user(),
            availability: AgentAvailability::from((int) $request->validated('availability')),
        );

        return $this->apiResponseUpdated(new AgentStatusResource($status));
    }
}
