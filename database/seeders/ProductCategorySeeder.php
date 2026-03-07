<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $parents = ProductCategory::factory()
            ->count(5)
            ->create();

        foreach ($parents as $parent) {
            ProductCategory::factory()
                ->count(2)
                ->create([
                    'parent_id' => $parent->id,
                ]);
        }
    }
}
