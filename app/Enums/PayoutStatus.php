<?php

namespace App\Enums;

enum PayoutStatus: int
{
    case PENDING = 1;
    case COMPLETED = 2;

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