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
        abort_if(! $admin->isAdmin(), 403, __('validation.custom.verify_admins'));
        abort_if($admin->id === auth()->id(), 403, __('validation.custom.action_denied'));
    }
}