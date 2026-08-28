<?php

declare(strict_types=1);

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;
use Essa\APIToolKit\Traits\DateFilter;

class SupportTicketFilters extends QueryFilters
{
    use DateFilter;

    protected array $allowedFilters = ['status', 'category', 'agent_id'];

    protected array $columnSearch = ['subject'];

    /**
     * Split the queue by who owns the ticket rather than by a column value.
     */
    public function assignment($value): void
    {
        match ($value) {
            'unassigned' => $this->builder->whereNull('agent_id'),
            'mine'       => $this->builder->where('agent_id', auth()->id()),
            default      => null,
        };
    }

    /**
     * Order the queue the way an agent works it.
     */
    public function sort($value): void
    {
        $this->builder->reorder();

        match ($value) {
            'oldest'   => $this->builder->orderBy('last_message_at', 'ASC'),
            'latest'   => $this->builder->orderBy('last_message_at', 'DESC'),
            default    => $this->builder->orderBy('last_message_at', 'DESC'),
        };
    }
}
