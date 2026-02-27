<?php

namespace App\Enums;

enum DefineStatus: int
{
    case INACTIVE = 0;
    case ACTIVE = 1;

    /**
     * Get the default status (ACTIVE)
     */
    public static function default(): self
    {
        return self::ACTIVE;
    }

    /**
     * Get all the possible values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}