<?php

namespace App\Http\Controllers\Api\V1\Customer\Concerns;

use App\Enums\BooleanStatus;
use App\Models\UserAddress;

trait CustomerAddressAuthorization
{
    /**
     * Authorize that the given address belongs to the authenticated customer.
     *
     * @param \App\Models\UserAddress $address The address instance being targeted by the action.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function authorizeCustomerAddress(UserAddress $address): void
    {
        abort_if(
            $address->user_id !== auth()->id(),
            403,
            __('addresses.action_denied')
        );
    }

    /**
     * Ensure the address is not currently set as default.
     * Prevents deleting the default address without reassigning it first.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function authorizeAddressDeletion(UserAddress $address): void
    {
        abort_if(
            $address->is_default === BooleanStatus::YES,
            422,
            __('addresses.cannot_delete_default_address')
        );
    }
}