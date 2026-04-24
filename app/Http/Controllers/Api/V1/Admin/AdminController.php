<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Api\V1\Admin\Concerns\AdminAuthorization;
use App\Http\Requests\Admin\AdminUser\CreateAdminRequest;
use App\Http\Requests\Admin\AdminUser\UpdateAdminRequest;
use App\Http\Resources\Admin\AdminUser\AdminUserResource;
use App\Http\Resources\Admin\AdminUser\ToggleAdminStatusResource;
use App\Models\User;
use App\Services\UserStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminController extends BaseApiController
{
    use AdminAuthorization;

    public function __construct(private readonly UserStatusService $userStatusService) {}

    public function index(): AnonymousResourceCollection
    {
        $admins = User::select('id', 'name', 'email', 'phone', 'status')
            ->admin()
            ->useFilters()
            ->latest()
            ->dynamicPaginate();

        return AdminUserResource::collection($admins);
    }

    public function store(CreateAdminRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['role'] = UserRole::ADMIN;
        $user = User::create($data);
        return $this->apiResponseStored(new AdminUserResource($user));
    }

    public function update(UpdateAdminRequest $request, User $admin): JsonResponse
    {
        $this->authorizeAdminAction($admin);
        $data = $request->validated();
        $admin->update($data);
        return $this->apiResponseUpdated(new AdminUserResource($admin));
    }

    public function destroy(User $admin): JsonResponse
    {
        $this->authorizeAdminAction($admin);
        $admin->delete();
        return $this->apiResponseDeleted();
    }

    /**
     * Toggle the status of an admin.
     */
    public function toggleStatus(User $admin): JsonResponse
    {
        $this->authorizeAdminAction($admin);
        $this->userStatusService->toggle($admin);
        return $this->apiResponseUpdated(new ToggleAdminStatusResource($admin));
    }
}