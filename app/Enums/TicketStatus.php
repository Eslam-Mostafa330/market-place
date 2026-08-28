<?php

namespace App\Enums;

enum TicketStatus: int
{
    case OPEN = 1;
    case ASSIGNED = 2;
    case RESOLVED = 3;
    case CLOSED = 4;

    /**
     * Get the default ticket status (OPEN)
     */
    public static function default(): self
    {
        return self::OPEN;
    }

    /**
     * Get all the possible values as an array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * The complete set of legal status transitions.
     *
     * @return array<int, list<self>>
     */
    public static function transitions(): array
    {
        return [
            self::OPEN->value     => [self::ASSIGNED, self::CLOSED],
            self::ASSIGNED->value => [self::RESOLVED, self::CLOSED, self::OPEN],
            self::RESOLVED->value => [self::ASSIGNED, self::CLOSED],
            self::CLOSED->value   => [],
        ];
    }

    /**
     * The statuses an agent may set by hand.
     */
    public static function agentAssignable(): array
    {
        return [
            self::RESOLVED->value,
            self::CLOSED->value,
        ];
    }

    /**
     * Determine whether this status may transition to the given one.
     */
    public function canTransitionTo(self $status): bool
    {
        return in_array($status, self::transitions()[$this->value], true);
    }

    /**
     * A closed ticket status is terminal and cannot be reopened
     */
    public function isClosed(): bool
    {
        return $this === self::CLOSED;
    }
}
