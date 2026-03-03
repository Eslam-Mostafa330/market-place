<?php

namespace App\Enums;

enum TokenAbility: int
{
    case ACCESS_API = 1;
    case ISSUE_ACCESS_TOKEN = 2;

    /**
     * Get all the possible values as an array
     */
    public static function values(): array
    {
        return [
            self::ACCESS_API->value,
            self::ISSUE_ACCESS_TOKEN->value,
        ];
    }
}