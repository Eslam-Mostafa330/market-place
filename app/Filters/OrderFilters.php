<?php

declare(strict_types=1);

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;
use Essa\APIToolKit\Traits\DateFilter;

class OrderFilters extends QueryFilters
{
    use DateFilter;

    protected array $allowedFilters = ['order_status', 'payment_status'];

    protected array $columnSearch = [];
}
