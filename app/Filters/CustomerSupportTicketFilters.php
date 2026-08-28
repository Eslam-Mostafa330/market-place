<?php

declare(strict_types=1);

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class CustomerSupportTicketFilters extends QueryFilters
{
    protected array $allowedFilters = ['status', 'category'];

    protected array $columnSearch = ['subject'];
}
