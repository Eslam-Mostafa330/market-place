<?php

namespace App\Enums;

enum OrderStatus: int
{
    case PENDING = 1;
    case CONFIRMED = 2;
    case PREPARING = 3;
    case PICKED = 4;
    case DELIVERED = 5;
    case CANCELLED = 6;
    case REFUNDED = 7;

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