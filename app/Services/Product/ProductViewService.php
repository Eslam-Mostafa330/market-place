<?php

namespace App\Services\Product;

use App\Jobs\CustomerPreference\RefreshCustomerPreferences;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductViewService
{
    /**
     * Records a product view for the authenticated customer.
     *
     * Upserts the view so each product appears once per customer.
     * Skips guests. Dispatch on first ever view
     */
    public function record(Product $product): void
    {
        if (! auth('sanctum')->check()) {
            return;
        }

        $customerId = auth('sanctum')->id();

        $inserted = DB::table('product_views')->insertOrIgnore([
            'id'           => Str::uuid(),
            'customer_id'  => $customerId,
            'product_id'   => $product->id,
            'viewed_at'    => now(),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        if (! $inserted) {
            return;
        }

        RefreshCustomerPreferences::throttledDispatch($customerId, 'view');
    }
}