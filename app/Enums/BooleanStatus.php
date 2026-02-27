<?php

namespace App\Enums;

enum BooleanStatus: int
{
    case YES = 1;
    case NO = 2;

    /**
     * Get the default boolean status (YES)
     */
    public static function default(): self
    {
        return self::YES;
    }

    /**
     * Get all the possible values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}