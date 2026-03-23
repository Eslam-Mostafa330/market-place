<?php

namespace Database\Seeders;

use Database\Seeders\Admin\AdminUserSeeder;
use Database\Seeders\Customer\CustomerProfileSeeder;
use Database\Seeders\Customer\CustomerUserSeeder;
use Database\Seeders\Customer\UserAddressSeeder;
use Database\Seeders\Rider\RiderProfileSeeder;
use Database\Seeders\Rider\RiderUserSeeder;
use Database\Seeders\Vendor\VendorProfileSeeder;
use Database\Seeders\Vendor\VendorUserSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            VendorUserSeeder::class,
            CustomerUserSeeder::class,
            RiderUserSeeder::class,
            BusinessCategorySeeder::class,
            ProductCategorySeeder::class,
            VendorProfileSeeder::class,
            RiderProfileSeeder::class,
            CustomerProfileSeeder::class,
            UserAddressSeeder::class,
            StoreSeeder::class,
            StoreBranchSeeder::class,
            ProductSeeder::class,
        ]);
    }
}