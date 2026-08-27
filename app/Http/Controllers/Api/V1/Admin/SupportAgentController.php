<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Api\V1\Admin\Concerns\AdminAuthorization;
use App\Http\Requests\Admin\SupportAgent\CreateSupportAgentRequest;
use App\Http\Requests\Admin\SupportAgent\UpdateSupportAgentRequest;
use App\Http\Resources\Admin\SupportAgent\SupportAgentResource;
use App\Http\Resources\Admin\SupportAgent\ToggleSupportAgentStatusResource;
use App\Models\User;
use App\Services\UserStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SupportAgentController extends BaseApiController
{
    use AdminAuthorization;

    public function __construct(private readonly UserStatusService $userStatusService) {}

    public function index(): AnonymousResourceCollection
    {
        $agents = User::select('id', 'name', 'email', 'phone', 'status')
            ->support()
            ->with('agentStatus')
            ->useFilters()
            ->latest()
            ->dynamicPaginate();

        return SupportAgentResource::collection($agents);
    }

    public function store(CreateSupportAgentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['role'] = UserRole::SUPPORT;

        $agent = User::create($data);

        return $this->apiResponseStored(new SupportAgentResource($agent));
    }

    public function update(UpdateSupportAgentRequest $request, User $agent): JsonResponse
    {
        $this->authorizeSupportAgentAction($agent);

        $agent->update($request->validated());

        return $this->apiResponseUpdated(new SupportAgentResource($agent));
    }

    public function destroy(User $agent): JsonResponse
    {
        $this->authorizeSupportAgentAction($agent);

        $agent->delete();

        return $this->apiResponseDeleted();
    }

    /**
     * Toggle the status of a support agent.
     */
    public function toggleStatus(User $agent): JsonResponse
    {
        $this->authorizeSupportAgentAction($agent);

        $this->userStatusService->toggle($agent);

        return $this->apiResponseUpdated(new ToggleSupportAgentStatusResource($agent));
    }
}
