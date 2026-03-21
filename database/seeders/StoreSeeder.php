<?php

namespace Database\Seeders;

use App\Enums\VendorVerificationStatus;
use App\Models\Store;
use App\Models\VendorProfile;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $vendors = VendorProfile::select('id')
            ->where('verification_status', VendorVerificationStatus::VERIFIED)
            ->get();

        foreach ($vendors as $vendor) {
            Store::factory()
                ->count(fake()->numberBetween(1, 10))
                ->for($vendor, 'vendorProfile')
                ->create();
        }
    }
}