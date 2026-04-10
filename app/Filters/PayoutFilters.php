<?php

declare(strict_types=1);

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;
use Essa\APIToolKit\Traits\DateFilter;

class PayoutFilters extends QueryFilters
{
    use DateFilter;

    protected array $allowedFilters = ['status'];

    protected array $columnSearch = [];

    /**
     * Use paid_at for date range filtering instead of created_at.
     */
    public function getDateColumnName(): string
    {
        return 'paid_at';
    }
}