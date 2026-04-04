<?php

namespace App\Enums;

enum CancellationReason: int
{
    case RIDER_OFF_DUTY = 1;
    case CUSTOMER_TOO_FAR = 2;
    case VEHICLE_ISSUE = 3;
    case PERSONAL_EMERGENCY = 4;
    case OTHER = 5;
    case CHANGED_MIND = 6;
    case RIDER_TOO_FAR = 7;
    case LONG_WAITING_TIME = 8;
    case PRICE_TOO_HIGH = 9;

    /**
     * Return non-cancellable statuses as integer values
     */
    public static function customerCancellationCases(): array
    {
        return [
            self::PERSONAL_EMERGENCY,
            self::OTHER,
            self::CHANGED_MIND,
            self::RIDER_TOO_FAR,
            self::RIDER_TOO_FAR,
            self::LONG_WAITING_TIME,
            self::PRICE_TOO_HIGH,
        ];
    }

    /**
     * Get all the possible values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}