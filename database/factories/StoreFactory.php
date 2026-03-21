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
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $company = $this->faker->company();
        $suffixes = ['Store', 'Market', 'Shop', 'Outlet', 'Hub', 'Express'];
        $baseName = $company . ' ' . $this->faker->randomElement($suffixes);
        $unique = $this->faker->numberBetween(1000, 9999);
        $name = "$baseName $unique";
        $slug = Str::slug($name);
        $description = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.';

        return [
            'name'                 => $name,
            'slug'                 => $slug,
            'description'          => $description,
            'logo'                 => 'stores/logos/' . $slug . '-' . $this->faker->randomNumber(4) . '.jpg',
            'image'                => 'stores/images/' . $slug . '-' . $this->faker->randomNumber(4) . '.png',
            'business_category_id' => BusinessCategory::inRandomOrder()->first()?->id,
        ];
    }
}