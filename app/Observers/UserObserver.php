<?php

namespace App\Observers;

use App\Enums\DefineStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\EmailVerificationService;

class UserObserver
{
    /**
     * Handle the User "creating" event.
     * This method automatically sets email_verified_at, phone_verified_at, and status to active for admin users when they are created. and sets status to active for vendor users.
     */
    public function creating(User $user): void
    {
        match ($user->role) {
            UserRole::ADMIN  => $this->setAdminDefaults($user),
            UserRole::VENDOR => $this->setVendorDefaults($user),
            default          => null,
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
        $this->handleStatusChange($user);
        $this->handleEmailChange($user);
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

    /**
     * Auto-verify and activate admin accounts upon creation.
     */
    private function setAdminDefaults(User $user): void
    {
        $user->email_verified_at = now();
        $user->phone_verified_at = now();
        $user->status            = DefineStatus::ACTIVE;
    }

    /**
     * Set vendor account as active upon creation.
     */
    private function setVendorDefaults(User $user): void
    {
        $user->status = DefineStatus::ACTIVE;
        app(EmailVerificationService::class)->sendVerificationEmail($user, request()->ip());
    }

    /**
     * Revoke all tokens when a user's status is set to inactive.
     */
    private function handleStatusChange(User $user): void
    {
        if ($user->wasChanged('status') && $user->status === DefineStatus::INACTIVE) {
            $user->tokens()->delete();
        }
    }

    /**
     * Re-verify admin email instantly or notify vendor to re-verify on email change.
     */
    private function handleEmailChange(User $user): void
    {
        if (! $user->wasChanged('email')) {
            return;
        }

        match ($user->role) {
            UserRole::ADMIN  => $user->updateQuietly(['email_verified_at' => now()]),
            UserRole::VENDOR => app(EmailVerificationService::class)->sendVerificationEmail($user, request()->ip()),
            default          => null,
        };
    }
}