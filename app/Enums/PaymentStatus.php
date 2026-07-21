<?php

namespace App\Enums;

enum PaymentStatus: int
{
    case PENDING = 1;
    case PAID = 2;
    case FAILED = 3;
    case REFUNDED = 4;

    /**
     * Statuses that no longer accept payment gateway state changes.
     *
     * Webhooks are delivered at-least-once and out of order, so an event arriving
     * for an order in one of these statuses is stale and must be ignored.
     */
    public static function terminalStatuses(): array
    {
        return [
            self::REFUNDED,
        ];
    }

    /**
     * Get the default payment status (PENDING)
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