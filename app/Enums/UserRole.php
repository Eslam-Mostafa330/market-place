<?php

namespace App\Enums;

enum UserRole: int
{
    case ADMIN = 1;
    case VENDOR = 2;
    case CUSTOMER = 3;
    case RIDER = 4;

    /**
     * Get the default user role (CUSTOMER)
     */
    public static function default(): self
    {
        return self::CUSTOMER;
    }

    /**
     * Get all the possible values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}