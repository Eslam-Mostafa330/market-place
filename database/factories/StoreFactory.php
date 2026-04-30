<?php

namespace Database\Factories;

use App\Models\BusinessCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Store>
 */
class StoreFactory extends Factory
{
    public function definition(): array
    {
        $category = BusinessCategory::inRandomOrder()->first();

        [$name, $slug] = $this->generateStoreName($category?->name);

        return [
            'name'                 => $name,
            'slug'                 => $slug,
            'description'          => $category?->description ?? 'Store description',
            'commission_rate'      => fake()->randomFloat(2, 5, 20),
            'logo'                 => 'stores/logos/' . $slug . '.jpg',
            'image'                => 'stores/images/' . $slug . '.png',
            'business_category_id' => $category?->id,
        ];
    }

    private function generateStoreName(?string $category): array
    {
        $names = match ($category) {
            'Groceries' => [
                'Fresh Basket',
                'Daily Mart',
                'Green Valley',
                'Family Grocers',
                'Prime Foods',
            ],
            'Food & Restaurants' => [
                'Golden Spoon',
                'Urban Bites',
                'Spice House',
                'Taste Haven',
                'Grill & Chill',
            ],
            'Home & Kitchen' => [
                'Cozy Living',
                'Home Essentials',
                'Kitchen Corner',
                'Modern Nest',
                'Comfort House',
            ],
            'Health & Pharmacy' => [
                'CarePlus',
                'Health Hub',
                'Wellness Store',
                'MediCare',
                'Life Aid',
            ],
            'Flowers & Gifts' => [
                'Bloom & Bliss',
                'Petal House',
                'Gift Corner',
                'Flora World',
                'Sweet Surprises',
            ],
            default => [$this->faker->company()],
        };

        $base = $this->faker->randomElement($names);
        $name = $base . ' ' . $this->faker->city();
        $slug = Str::slug($name);

        return [$name, $slug];
    }
}