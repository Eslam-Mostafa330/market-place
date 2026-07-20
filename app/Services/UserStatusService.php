<?php

namespace App\Services;

use App\Enums\DefineStatus;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\DB;

class UserStatusService
{
    public function __construct(private readonly AuthService $authService) {}

    /**
     * Toggle the active/inactive status of a user and persist the change.
     *
     * Deactivating a user revokes all of their access
     * (tokens and web sessions) in the same transaction
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
                $this->authService->revokeAllAccess($user);
            }
        });

        return $newStatus;
    }
}