<?php

use App\Enums\UserRole;

// List of user roles for which 2FA is required, and related settings
return [
    'enabled_for_roles' => [
        UserRole::ADMIN->value,
    ],

    'otp_expires_in_minutes'   => 10,
    'device_trusted_for_days'  => 30,
    'max_attempts'             => 3,
    'attempt_cooldown_minutes' => 1,
];