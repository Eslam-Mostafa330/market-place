<?php

namespace App\Services;

use App\Enums\DefineStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserStatusService
{
    public function __construct(private AuthService $authService) {}

    /**
     * Toggle the active/inactive status of a user and persist the change.
     *
     * If the user is being deactivated, all of their active access tokens
     * will be revoked immediately to terminate any ongoing sessions.
     * The status update and token revocation are wrapped in a transaction
     * to ensure consistency.
     *
     * @param  User  $user  The user whose status will be toggled.
     * @return DefineStatus The new status after toggling.
     */
    public function toggle(User $user): DefineStatus
    {
        $newStatus = $user->status === DefineStatus::ACTIVE
            ? DefineStatus::INACTIVE
            : DefineStatus::ACTIVE;

        DB::transaction(function () use ($user, $newStatus) {
            $user->update(['status' => $newStatus]);

            if ($newStatus === DefineStatus::INACTIVE) {
                $this->authService->revokeAllTokens($user);
            }
        });

        return $newStatus;
    }
}