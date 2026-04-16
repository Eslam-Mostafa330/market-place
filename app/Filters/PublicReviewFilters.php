<?php

declare(strict_types=1);

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;
use Essa\APIToolKit\Traits\DateFilter;

class PublicReviewFilters extends QueryFilters
{
    use DateFilter;

    protected array $allowedFilters = ['rate'];

    public function sort($value): void
    {
        $this->builder->reorder();

        match ($value) {
            'highest' => $this->builder->orderBy('rate', 'DESC'),
            'lowest'  => $this->builder->orderBy('rate', 'ASC'),
        };
    }
}