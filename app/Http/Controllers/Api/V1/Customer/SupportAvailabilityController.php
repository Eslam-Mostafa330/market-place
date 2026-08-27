<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\Support\SupportPresenceService;
use Illuminate\Http\JsonResponse;

class SupportAvailabilityController extends BaseApiController
{
    public function __construct(private readonly SupportPresenceService $supportPresenceService) {}

    /**
     * Tell the customer whether the desk is staffed.
     */
    public function __invoke(): JsonResponse
    {
        return $this->apiResponseShow($this->supportPresenceService->customerSnapshot());
    }
}
