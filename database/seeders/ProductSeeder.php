<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::all();
        $categories = ProductCategory::all();

        foreach ($stores as $store) {

            $productCount = fake()->numberBetween(3, 5);

            for ($i = 0; $i < $productCount; $i++) {

                $category = $categories->random();

                $base = fake()->randomElement([
                    'Classic',
                    'Premium',
                    'Fresh',
                    'Special',
                ]);

                $productName = "{$base} {$category->name}";

                Product::factory()->create([
                    'store_id' => $store->id,
                    'product_category_id' => $category->id,
                    'name' => $productName . ' ' . ($i + 1),
                    'slug' => Str::slug($productName . '-' . $store->id . '-' . $i),
                ]);
            }
        }
    }
}