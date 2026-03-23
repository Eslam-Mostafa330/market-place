<?php

declare(strict_types=1);

namespace App\Filters;

use App\Models\ProductCategory;
use Essa\APIToolKit\Filters\QueryFilters;

class ProductFilters extends QueryFilters
{
    protected array $allowedFilters = ['status'];

    protected array $columnSearch = ['name'];

    public function category(string $slug): void
    {
        $this->builder->whereIn('product_category_id', ProductCategory::select('id')->where('slug', $slug));
    }
}