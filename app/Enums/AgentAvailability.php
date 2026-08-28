<?php

namespace App\Enums;

enum AgentAvailability: int
{
    case OFFLINE = 1;
    case ONLINE = 2;

    /**
     * Get the default availability (OFFLINE)
     */
    public static function default(): self
    {
        return self::OFFLINE;
    }

    /**
     * Get all the possible values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
