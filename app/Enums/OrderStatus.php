<?php

namespace App\Enums;

enum OrderStatus: int
{
    case PENDING = 1;
    case ACCEPTED = 2;
    case PREPARING = 3;
    case WAITING_RIDER = 4;
    case RIDER_ASSIGNED = 5;
    case PICKED_UP = 6;
    case IN_TRANSIT = 7;
    case DELIVERED = 8;
    case CANCELLED = 9;

    /**
     * Get the default order status (PENDING)
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