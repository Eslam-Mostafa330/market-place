<?php

namespace App\Services\Product;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductStockService
{
    /**
     * Reduce stock for the given products in a single statement.
     *
     * @param array<string, int> $quantities Map of product_id => quantity to remove
     */
    public function decrement(array $quantities): void
    {
        $this->apply($quantities, '-');
    }

    /**
     * Return stock for the given products in a single statement.
     *
     * Used to compensate a cancelled order. Quantities are added back exactly as
     * they were removed at placement, so a cancellation restores the catalog to
     * its pre-order state.
     *
     * @param array<string, int> $quantities Map of product_id => quantity to return
     */
    public function restore(array $quantities): void
    {
        $this->apply($quantities, '+');
    }

    /**
     * Apply a stock adjustment to multiple products in a single query.
     *
     * Uses bound values and preserves quantities for unmatched rows.
     *
     * @param array<string, int> $quantities Map of product_id => quantity
     * @param string             $operator   '+' or '-'
     */
    private function apply(array $quantities, string $operator): void
    {
        if ($quantities === []) {
            return;
        }

        $cases    = [];
        $bindings = [];

        foreach ($quantities as $productId => $quantity) {
            $cases[]    = "WHEN id = ? THEN quantity {$operator} ?";
            $bindings[] = $productId;
            $bindings[] = (int) $quantity;
        }

        $ids          = array_keys($quantities);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        DB::update(
            'UPDATE ' . (new Product())->getTable()
            . ' SET quantity = CASE ' . implode(' ', $cases) . ' ELSE quantity END, updated_at = ?'
            . ' WHERE id IN (' . $placeholders . ')',
            [...$bindings, now(), ...$ids]
        );
    }
}
