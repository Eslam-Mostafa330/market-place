<?php

namespace App\Enums;

enum AddressType: int
{
    case HOME = 1;
    case WORK = 2;
    case OTHER = 3;

    /**
     * Get the default address status (HOME)
     */
    public static function default(): self
    {
        return self::HOME;
    }

    /**
     * Get all the possible values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}