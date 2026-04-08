<?php

declare(strict_types=1);

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;
use Essa\APIToolKit\Traits\DateFilter;

class CouponFilters extends QueryFilters
{
    use DateFilter;

    protected array $allowedFilters = ['status', 'coupon_type'];

    protected array $columnSearch = ['name', 'code'];

    /**
     * Use expires_at for date range filtering instead of created_at.
     */
    public function getDateColumnName(): string
    {
        return 'expires_at';
    }
}