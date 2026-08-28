<?php

namespace App\Enums;

enum TicketCategory: int
{
    case ORDER = 1;
    case PAYMENT = 2;
    case DELIVERY = 3;
    case PRODUCT = 4;
    case ACCOUNT = 5;
    case OTHER = 6;

    /**
     * Get the default ticket category (OTHER)
     */
    public static function default(): self
    {
        return self::OTHER;
    }

    /**
     * Get all the possible values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
