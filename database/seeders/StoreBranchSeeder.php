<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\StoreBranch;
use Illuminate\Database\Seeder;

class StoreBranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stores = Store::get();

        foreach ($stores as $store) {
            StoreBranch::factory()
                ->count(fake()->numberBetween(1, 7))
                ->for($store)
                ->create();
        }
    }
}
