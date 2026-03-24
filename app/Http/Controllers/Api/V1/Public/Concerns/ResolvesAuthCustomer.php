<?php

namespace App\Http\Controllers\Api\V1\Public\Concerns;

use App\Enums\UserRole;
use App\Models\User;

trait ResolvesAuthCustomer
{
    /**
     * Resolve the authenticated user as a customer.
     *
     * Returns the authenticated user only if their role is {@see UserRole::CUSTOMER},
     * otherwise returns null — preventing vendor/rider/admin accounts from being
     * treated as customers in customer-specific logic.
     *
     * @return \App\Models\User|null
     */
    protected function authCustomer(): ?User
    {
        $user = auth('sanctum')->user();
        return $user?->role === UserRole::CUSTOMER ? $user : null;
    }
}