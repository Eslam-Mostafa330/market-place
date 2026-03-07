<?php

declare(strict_types=1);

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class UserFilters extends QueryFilters
{
    protected array $allowedFilters = ['status'];

    protected array $columnSearch = ['name', 'email', 'phone'];
}
