<?php

declare(strict_types=1);

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class UserAddressFilters extends QueryFilters
{
    protected array $allowedFilters = ['is_default'];

    protected array $columnSearch = [];
}
