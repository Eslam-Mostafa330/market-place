<?php

namespace App\Enums;

enum RiderAvailability: int
{
    case AVAILABLE = 1;
    case UNAVAILABLE = 2;

    /**
     * Get the default status (UNAVAILABLE)
     */
    public static function default(): self
    {
        return self::UNAVAILABLE;
    }

    /**
     * Get all the possible values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}