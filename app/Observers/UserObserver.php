<?php

namespace App\Observers;

use App\Enums\DefineStatus;
use App\Enums\UserRole;
use App\Models\User;

class UserObserver
{
    /**
     * Handle the User "creating" event.
     * This method automatically sets email_verified_at, phone_verified_at, and status to active for admin users when they are created.
     */
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
     * This method automatically deletes API tokens when an admin user is deactivated, and resets email verification when an admin's email is changed.
     */
    public function updated(User $user): void
    {
        if ($user->wasChanged('status') && $user->status === DefineStatus::INACTIVE) {
            $user->tokens()->delete();
        }

        if ($user->wasChanged('email')) {
            match ($user->role) {
                UserRole::ADMIN  => $user->updateQuietly(['email_verified_at' => now()]),
                default => null,
            };
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
