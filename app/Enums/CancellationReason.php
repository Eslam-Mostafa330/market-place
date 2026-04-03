<?php

namespace App\Enums;

enum CancellationReason: int
{
    case RIDER_OFF_DUTY = 1;
    case CUSTOMER_TOO_FAR = 2;
    case VEHICLE_ISSUE = 3;
    case PERSONAL_EMERGENCY = 4;
    case OTHER = 5;

    /**
     * Get all the possible values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}