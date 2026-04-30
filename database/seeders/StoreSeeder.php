<?php

namespace Database\Seeders;

use App\Enums\VendorVerificationStatus;
use App\Models\BusinessCategory;
use App\Models\Store;
use App\Models\VendorProfile;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $vendors = VendorProfile::where('verification_status', VendorVerificationStatus::VERIFIED)
            ->pluck('id');

        $categories = BusinessCategory::all();

        foreach ($categories as $category) {
            Store::factory()
                ->count(5)
                ->state(fn () => [
                    'business_category_id' => $category->id,
                    'vendor_profile_id'    => $vendors->random(),
                ])
                ->create();
        }
    }
}