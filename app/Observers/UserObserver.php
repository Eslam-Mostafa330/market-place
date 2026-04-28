<?php

namespace App\Observers;

use App\Enums\DefineStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\Auth\EmailVerificationService;
use Illuminate\Support\Facades\Cache;

class UserObserver
{
    public function __construct(private EmailVerificationService $emailVerificationService) {}

    /**
     * Handle the User "creating" event.
     * Set default attributes before inserting into the database.
     */
    public function creating(User $user): void
    {
        match ($user->role) {
            UserRole::ADMIN => $this->setAdminDefaults($user),
            default         => $this->setDefaultUserStatus($user),
        };
    }

    /**
     * Handle the User "created" event.
     * Send verification emails after the user exists in the database.
     */
    public function created(User $user): void
    {
        if (in_array($user->role, [UserRole::VENDOR, UserRole::CUSTOMER, UserRole::RIDER])) {
            $this->emailVerificationService->sendVerificationEmail($user, request()->ip());
        }

        $this->clearUserCountCache();
    }

    /**
     * Handle the User "updating" event.
     * Update verification state before the database update happens.
     */
    public function updating(User $user): void
    {
        if (! $user->isDirty('email')) {
            return;
        }

        $user->email_verified_at = match ($user->role) {
            UserRole::ADMIN => now(),
            default         => null,
        };
    }

    /**
     * Handle the User "updated" event.
     * Trigger side effects after update is completed.
     */
    public function updated(User $user): void
    {
        $this->handleStatusChange($user);

        if ($user->wasChanged('email')) {
            $this->handleEmailChange($user);
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        $this->clearUserCountCache();
    }

    /**
     * Revoke all tokens when a user becomes inactive.
     */
    private function handleStatusChange(User $user): void
    {
        if ($user->wasChanged('status') && $user->status === DefineStatus::INACTIVE) {
            $user->tokens()->delete();
        }
    }

    /**
     * Send verification email when vendor/customer email changes.
     */
    private function handleEmailChange(User $user): void
    {
        if (! in_array($user->role, [UserRole::VENDOR, UserRole::CUSTOMER, UserRole::RIDER])) {
            return;
        }

        $this->emailVerificationService->sendVerificationEmail($user, request()->ip());
    }

    /**
     * Auto verify and activate admin accounts.
     */
    private function setAdminDefaults(User $user): void
    {
        $user->email_verified_at = now();
        $user->phone_verified_at = now();
        $user->status            = DefineStatus::ACTIVE;
    }

    /**
     * Set default status for non-admin users.
     */
    private function setDefaultUserStatus(User $user): void
    {
        $user->status = DefineStatus::ACTIVE;
    }

    /**
     * Clear cached system-wide counts used in the admin dashboard.
     *
     * This is triggered when a user is created or deleted.
     */
    private function clearUserCountCache(): void
    {
        Cache::forget('admin_system_counts');
    }
}