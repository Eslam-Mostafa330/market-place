<?php

declare(strict_types=1);

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class BusinessCategoryFilters extends QueryFilters
{
    protected array $allowedFilters = [];

    protected array $columnSearch = ['name'];
}
