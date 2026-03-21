<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Store;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stores = Store::select('id')->get();
        $categories = ProductCategory::pluck('id')->toArray();

        foreach ($stores as $store) {
            Product::factory()
                ->count(fake()->numberBetween(5, 10))
                ->for($store)
                ->create([
                    'product_category_id' => fake()->randomElement($categories),
                ]);
        }
    }
}