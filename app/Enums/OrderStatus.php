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
    case DELIVERED = 7;
    case CANCELLED = 8;

    /**
     * Get the default order status (PENDING)
     */
    public static function default(): self
    {
        return self::PENDING;
    }

    /**
     * Return non-cancellable statuses as integer values
     */
    public static function nonCancellableStatuses(): array
    {
        return [
            self::DELIVERED,
            self::CANCELLED,
        ];
    }

    /**
     * The complete set of legal status transitions.
     *
     * Keeping the rules in one place is what stops them drifting: previously each
     * service asserted its own expected source status independently, so the fact
     * that (for example) a picked-up order may only become delivered or cancelled
     * was never written down anywhere and could not be reviewed as a whole.
     *
     * DELIVERED and CANCELLED are terminal.
     *
     * @return array<int, list<self>>
     */
    public static function transitions(): array
    {
        return [
            self::PENDING->value        => [self::ACCEPTED, self::CANCELLED],
            self::ACCEPTED->value       => [self::PREPARING, self::CANCELLED],
            self::PREPARING->value      => [self::WAITING_RIDER, self::CANCELLED],
            self::WAITING_RIDER->value  => [self::RIDER_ASSIGNED, self::CANCELLED],
            self::RIDER_ASSIGNED->value => [self::PICKED_UP, self::WAITING_RIDER, self::CANCELLED],
            self::PICKED_UP->value      => [self::DELIVERED, self::CANCELLED],
            self::DELIVERED->value      => [],
            self::CANCELLED->value      => [],
        ];
    }

    /**
     * Whether this status may legally move to the given status.
     */
    public function canTransitionTo(self $target): bool
    {
        return in_array($target, self::transitions()[$this->value] ?? [], true);
    }

    /**
     * Whether this status is terminal — no further transitions are possible.
     */
    public function isTerminal(): bool
    {
        return self::transitions()[$this->value] === [];
    }

    /**
     * Get all the possible values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}