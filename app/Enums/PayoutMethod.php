<?php

namespace App\Enums;

enum PayoutMethod: int
{
    case BANK_TRANSFER = 1;
    case PHONE_WALLET = 2;
    case CASH = 3;
    case OTHER = 4;

    /**
     * Return methods that require additional notes.
     */
    public static function requiresForNotes(): array
    {
        return [
            self::CASH->value,
            self::OTHER->value,
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