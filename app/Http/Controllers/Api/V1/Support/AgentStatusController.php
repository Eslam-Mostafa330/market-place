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
     * The agent's own presence, as the console last left it.
     */
    public function show(): JsonResponse
    {
        $status = $this->supportPresenceService->statusFor(auth()->user());

        return $this->apiResponseShow(new AgentStatusResource($status));
    }

    /**
     * Go online, step away, or clock off.
     *
     * The console also calls this on a timer while open, to keep the agent's presence up to date.
     */
    public function update(UpdateAvailabilityRequest $request): JsonResponse
    {
        $status = $this->supportPresenceService->heartbeat(
            agent: $request->user(),
            availability: AgentAvailability::from((int) $request->validated('availability')),
        );

        return $this->apiResponseUpdated(new AgentStatusResource($status));
    }
}
