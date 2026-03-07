<?php

namespace App\Observers;

use App\Enums\DefineStatus;
use App\Enums\UserRole;
use App\Models\User;

class UserObserver
{
    public function creating(User $user): void
    {
        match ($user->role) {
            UserRole::ADMIN => [
                $user->email_verified_at = now(),
                $user->phone_verified_at = now(),
                $user->status            = DefineStatus::ACTIVE,
            ],

            default => null,
        };
    }

    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        //
    }

    /**
     * Handle the User "updated" event.
     * This method checks if the user's status has changed to inactive and if so, it deletes all of the user's access tokens to prevent further access.
     */
    public function updated(User $user): void
    {
        if ($user->wasChanged('status') && $user->status === DefineStatus::INACTIVE) {
            $user->tokens()->delete();
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
