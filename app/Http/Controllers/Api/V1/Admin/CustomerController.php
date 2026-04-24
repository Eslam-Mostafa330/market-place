<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Admin\CustomerUser\CreateCustomerRequest;
use App\Http\Requests\Admin\CustomerUser\UpdateCustomerRequest;
use App\Http\Resources\Admin\CustomerUser\CustomerUserResource;
use App\Http\Resources\Admin\CustomerUser\ToggleCustomerStatusResource;
use App\Models\User;
use App\Services\UserStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class CustomerController extends BaseApiController
{
    public function __construct(private readonly UserStatusService $userStatusService) {}

    public function index(): AnonymousResourceCollection
    {
        $customers = User::select('id', 'name', 'email', 'phone', 'status')
            ->customer()
            ->useFilters()
            ->latest()
            ->dynamicPaginate();

        return CustomerUserResource::collection($customers);
    }

    public function store(CreateCustomerRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['role'] = UserRole::CUSTOMER;
            $user = User::create($data);
            $user->customerProfile()->create();
            return $user;
        });

        return $this->apiResponseStored(new CustomerUserResource($user));
    }

    public function update(UpdateCustomerRequest $request, User $customer): JsonResponse
    {
        abort_unless($customer->isCustomer(), 422, __('validation.custom.verify_customers'));
        $data = $request->validated();
        $customer->update($data);
        return $this->apiResponseUpdated(new CustomerUserResource($customer));
    }

    public function destroy(User $customer): JsonResponse
    {
        abort_unless($customer->isCustomer(), 422, __('validation.custom.verify_customers'));
        $customer->delete();
        return $this->apiResponseDeleted();
    }

    /**
     * Toggle the status of a customer.
     */
    public function toggleStatus(User $customer): JsonResponse
    {
        abort_unless($customer->isCustomer(), 422, __('validation.custom.verify_customers'));
        $this->userStatusService->toggle($customer);
        return $this->apiResponseUpdated(new ToggleCustomerStatusResource($customer));
    }
}
