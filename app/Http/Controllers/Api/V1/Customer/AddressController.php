<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Enums\BooleanStatus;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Api\V1\Customer\Concerns\CustomerAddressAuthorization;
use App\Http\Requests\Customer\Address\CreateCustomerAddressRequest;
use App\Http\Requests\Customer\Address\UpdateCustomerAddressRequest;
use App\Http\Resources\Customer\Address\CustomerAddressListResource;
use App\Http\Resources\Customer\Address\CustomerAddressResource;
use App\Http\Resources\Customer\Address\DefaultAddressResource;
use App\Models\UserAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class AddressController extends BaseApiController
{
    use CustomerAddressAuthorization;

    public function index(): AnonymousResourceCollection
    {
        $addresses = UserAddress::select('id', 'country', 'city', 'state', 'address_line_1', 'is_default')
            ->where('user_id', auth()->id())
            ->latest()
            ->useFilters()
            ->dynamicPaginate();

        return CustomerAddressListResource::collection($addresses);
    }

    public function show(UserAddress $address): JsonResponse
    {
        $this->authorizeCustomerAddress($address);

        $address = UserAddress::select([
                'user_addresses.id',
                'user_addresses.country',
                'user_addresses.city',
                'user_addresses.state',
                'user_addresses.address_line_1',
                'user_addresses.address_line_2',
                'user_addresses.additional_phone',
                'user_addresses.postal_code',
                'user_addresses.additional_info',
                'user_addresses.latitude',
                'user_addresses.longitude',
                'user_addresses.is_default',
                'user_addresses.type',
                DB::raw('COALESCE(user_addresses.contact_phone, users.phone) as contact_phone'),
            ])
            ->join('users', 'users.id', '=', 'user_addresses.user_id')
            ->where('user_addresses.id', $address->id)
            ->firstOrFail();

        return $this->apiResponseShow(new CustomerAddressResource($address));
    }

    public function store(CreateCustomerAddressRequest $request): JsonResponse
    {
        $data = $request->validated();
        $address = UserAddress::create($data);
        return $this->apiResponseStored(new CustomerAddressResource($address));
    }

    public function update(UpdateCustomerAddressRequest $request, UserAddress $address): JsonResponse
    {
        $this->authorizeCustomerAddress($address);
        $data = $request->validated();
        $address->update($data);
        return $this->apiResponseUpdated(new CustomerAddressResource($address));
    }

    public function destroy(UserAddress $address): JsonResponse
    {
        $this->authorizeCustomerAddress($address);
        $this->authorizeAddressDeletion($address);
        $address->delete();
        return $this->apiResponseDeleted();
    }

    /**
     * Set the given address as the default for the authenticated customer.
     * All other addresses for this customer will be demoted to not default.
     */
    public function setDefault(UserAddress $address): JsonResponse
    {
        $this->authorizeCustomerAddress($address);
        UserAddress::where('user_id', auth()->id())->update(['is_default' => BooleanStatus::NO]);
        $address->updateQuietly(['is_default' => BooleanStatus::YES]);
        return $this->apiResponseUpdated(new DefaultAddressResource($address));
    }
}