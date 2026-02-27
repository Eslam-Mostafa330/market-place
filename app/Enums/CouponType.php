<?php

namespace App\Enums;

enum CouponType: int
{
    case FIXED = 1;
    case PERCENTAGE = 2;

    /**
     * Get all the possible values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}