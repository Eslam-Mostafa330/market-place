<?php

namespace App\Enums;

enum VendorVerificationStatus: int
{
    case VERIFIED = 1;
    case PENDING = 2;
    case REJECTED = 3;
    case INCOMPLETE = 4;

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

    /**
    * Get all the possible values as an array for admin actions (VERIFIED and REJECTED)
    */
    public static function allowedAdminActions(): array
    {
        return [
            self::VERIFIED->value,
            self::REJECTED->value,
        ];
    }
}