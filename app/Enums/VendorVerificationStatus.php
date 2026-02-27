<?php

namespace App\Enums;

enum VendorVerificationStatus: int
{
    case VERIFIED = 1;
    case PENDING = 2;
    case REJECTED = 3;

    /**
     * Get the default status (PENDING)
     */
    public static function default(): self
    {
        return self::PENDING;
    }

    /**
     * Get all the possible values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}