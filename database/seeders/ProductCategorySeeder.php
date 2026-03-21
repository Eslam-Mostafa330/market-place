<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // Food (for restaurants)
            'Main Dishes' => [
                'Pizza',
                'Burgers',
                'Pasta',
                'Grilled',
            ],
            'Sides' => [
                'Fries',
                'Salads',
                'Appetizers',
            ],
            'Desserts' => [
                'Cakes',
                'Ice Cream',
            ],
            'Beverages' => [
                'Soft Drinks',
                'Hot Drinks',
                'Fresh Juices',
            ],

            // Grocery categories
            'Fruits & Vegetables' => [
                'Fresh Fruits',
                'Fresh Vegetables',
            ],
            'Dairy & Eggs' => [
                'Milk',
                'Cheese',
                'Eggs',
            ],
            'Bakery' => [
                'Bread',
                'Baked Goods',
            ],
            'Snacks' => [
                'Chips',
                'Chocolate',
                'Biscuits',
            ],

            // Electronics categories
            'Mobiles' => [
                'Apple',
                'Samsung',
                'Oppo',
            ],
            'Laptops' => [
                'Asus',
                'Dell',
                'Lenovo',
            ],

            // Fashion categories (parent)
            'Men Clothing' => [],

            // Health & Personal Care categories
            'Medicines' => [
                'Pain Relief',
                'Cold & Flu',
                'Digestive Health',
                'Heart & Blood Pressure',
            ],
            'Vitamins' => [
                'Multivitamins',
                'B-Complex',
                'Prenatal Vitamins',
                'Children\'s Vitamins',
                'Iron Supplements',
                'Omega-3 / Fish Oil',
            ],
        ];

        foreach ($categories as $parentName => $children) {
            $parent = ProductCategory::updateOrCreate(
                [
                    'name'      => $parentName,
                    'parent_id' => null,
                ],
                [
                    'name' => $parentName,
                    'slug' => Str::slug($parentName),
                ]
            );

            foreach ($children as $childName) {
                ProductCategory::updateOrCreate(
                    [
                        'name'      => $childName,
                        'parent_id' => $parent->id,
                    ],
                    [
                        'name'      => $childName,
                        'slug'      => Str::slug($childName),
                        'parent_id' => $parent->id,
                    ]
                );
            }
        }
    }
}