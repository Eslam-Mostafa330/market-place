<?php

namespace Database\Seeders;

use App\Models\BusinessCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BusinessCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name'        => 'Groceries',
                'description' => 'Daily essentials, fresh produce, dairy, bakery, household items and supermarket products.',
            ],
            [
                'name'        => 'Food & Restaurants',
                'description' => 'Restaurants, fast food, cafes, home-cooked meals, pizza, oriental food, desserts and more.',
            ],
            [
                'name'        => 'Home & Kitchen',
                'description' => 'Furniture, kitchenware, home decor, bedding, appliances and household essentials.',
            ],
            [
                'name'        => 'Health & Pharmacy',
                'description' => 'Medicines, vitamins, medical devices, baby care, health supplements and first aid.',
            ],
            [
                'name'        => 'Flowers & Gifts',
                'description' => 'Bouquets, plants, gifts, occasions presents and personalized items.',
            ],
        ];

        foreach ($categories as $data) {
            $name = $data['name'];

            BusinessCategory::updateOrCreate(
                ['name' => $name],
                [
                    'name'        => $name,
                    'slug'        => Str::slug($name),
                    'image'       => 'business-categories/images/' . Str::slug($name, '-') . '.jpg',
                    'description' => $data['description'],
                ]
            );
        }
    }
}