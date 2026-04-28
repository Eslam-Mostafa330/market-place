<?php

namespace App\Http\Controllers\Api\V1\Admin\Concerns;

use App\Models\User;

trait AdminAuthorization
{
    /**
     * Authorize actions performed on admin accounts.
     *
     * Ensures that the targeted user record belongs to an admin
     * and prevents the authenticated admin from performing actions
     * on their own account (such as updating or deleting themselves).
     *
     * @param \App\Models\User $admin The user instance being targeted by the action.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function authorizeAdminAction(User $admin): void
    {
        abort_if(! $admin->isAdmin(), 404);
        abort_if($admin->id === auth()->id(), 403, __('admins.action_denied'));
    }

    /**
     * Authorize actions performed on rider accounts.
     *
     * Ensures that the targeted user record belongs to a rider.
     * Prevents performing rider-specific operations on non-rider accounts.
     *
     * @param \App\Models\User $rider The user instance being targeted by the action.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function authorizeRiderAction(User $rider): void
    {
        abort_if(! $rider->isRider(), 404);
    }

    /**
     * Authorize actions performed on vendor accounts.
     *
     * Ensures that the targeted user record belongs to a vendor.
     * Prevents performing vendor-specific operations on non-vendor accounts.
     *
     * @param \App\Models\User $vendor The user instance being targeted by the action.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function authorizeVendorAction(User $vendor): void
    {
        abort_if(! $vendor->isVendor(), 404);
    }

    /**
     * Authorize actions performed on customer accounts.
     *
     * Ensures that the targeted user record belongs to a customer.
     * Prevents performing customer-specific operations on non-customer accounts.
     *
     * @param \App\Models\User $customer The user instance being targeted by the action.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function authorizeCustomerAction(User $customer): void
    {
        abort_if(! $customer->isCustomer(), 404);
    }
}