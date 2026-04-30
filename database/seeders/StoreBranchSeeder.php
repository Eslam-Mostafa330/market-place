<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\StoreBranch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StoreBranchSeeder extends Seeder
{
    public function run(): void
    {
        $stores = Store::all();

        $cityAreas = [
            'Cairo' => ['Nasr City', 'Heliopolis', 'Maadi', 'Downtown', 'Zamalek'],
            'Giza' => ['Dokki', 'Mohandessin', 'Haram', 'Sheikh Zayed'],
            'New Cairo' => ['Fifth Settlement', 'Rehab', 'Madinaty'],
        ];

        foreach ($stores as $store) {
            $city = fake()->randomElement(array_keys($cityAreas));

            $areas = collect($cityAreas[$city])->shuffle()->take(3);

            foreach ($areas as $area) {
                StoreBranch::factory()->create([
                    'store_id' => $store->id,
                    'city'     => $city,
                    'area'     => $area,
                    'name'     => "{$area} Branch",
                    'slug'     => Str::slug("{$area} Branch"),
                    'address'  => "{$area}, {$city}",
                ]);
            }
        }
    }
}