<?php

declare(strict_types=1);

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class StoreFilters extends QueryFilters
{
    protected array $allowedFilters = ['business_category_id'];

    protected array $columnSearch = ['name'];
}
