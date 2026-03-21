<?php

namespace Database\Seeders\Vendor;

use App\Enums\VendorVerificationStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VendorProfileSeeder extends Seeder
{
    public function run(): void
    {
        $vendorUsers = User::where('role', UserRole::VENDOR)->get();

        $vendorData = [
            ['name' => 'Fresh Mart',               'description' => 'Your trusted neighborhood store for fresh groceries and daily essentials.'],
            ['name' => 'Nile Grocery',             'description' => 'Quality fruits, vegetables, meat, and household items delivered fast across Cairo.'],
            ['name' => 'Delta Foods',              'description' => 'Fresh produce, dairy, bakery items and everyday groceries with quick delivery.'],
            ['name' => 'Royal Bakery',             'description' => 'Freshly baked bread, pastries, cakes and traditional Egyptian sweets every day.'],
            ['name' => 'Star Electronics',         'description' => 'Latest smartphones, accessories, laptops and home electronics at great prices.'],
            ['name' => 'Golden Fashion',           'description' => 'Trendy clothing, shoes and accessories for men, women and kids.'],
            ['name' => 'City Pharmacy',            'description' => 'Medicines, vitamins, personal care products and health essentials.'],
            ['name' => 'Quick Market',             'description' => 'Convenience store with snacks, drinks, cigarettes and daily necessities 24/7.'],
            ['name' => 'Happy Chicken',            'description' => 'Fresh chicken, meat, poultry and ready-to-cook products with same-day delivery.'],
            ['name' => 'Sweet Corner',             'description' => 'Desserts, chocolates, ice cream, cakes and all kinds of sweets.'],
            ['name' => 'Tech Zone',                'description' => 'Gadgets, mobile accessories, gaming gear and smart home devices.'],
            ['name' => 'Home Essentials',          'description' => 'Kitchenware, cleaning supplies, home decor and everyday household items.'],
            ['name' => 'Veggie Basket',            'description' => 'Fresh fruits, vegetables, organic produce and healthy options.'],
            ['name' => 'Meat Master',              'description' => 'Premium cuts of beef, lamb, poultry and fresh seafood.'],
            ['name' => 'Daily Dairy',              'description' => 'Milk, cheese, yogurt, eggs and all dairy products.'],
            ['name' => 'Snack Attack',             'description' => 'Chips, nuts, biscuits, chocolates and all your favorite snacks.'],
            ['name' => 'Coffee & More',            'description' => 'Coffee beans, tea, hot & cold drinks and café-style snacks.'],
            ['name' => 'Fashion Avenue',           'description' => 'Modern and modest fashion for the whole family.'],
            ['name' => 'Gadget World',             'description' => 'Latest tech gadgets, earphones, chargers and smart accessories.'],
            ['name' => 'Healthy Life Shop',        'description' => 'Vitamins, supplements, organic foods and wellness products.'],
            ['name' => 'Baby Joy Store',           'description' => 'Baby milk, diapers, toys, clothes and all baby care essentials.'],
        ];

        $index = 0;

        foreach ($vendorUsers as $user) {
            $data = $vendorData[$index % count($vendorData)];

            VendorProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'business_name'        => $data['name'],
                    'business_license'     => fake()->optional(0.7)->numerify('LIC-######'),
                    'business_description' => $data['description'],
                    'business_phone'       => fake()->unique()->numerify('01#########'),
                    'business_email'       => 'info@' . Str::slug($data['name'], '-') . '.com',
                    'rating'               => fake()->randomFloat(2, 3.5, 4.9),
                    'total_orders'         => fake()->numberBetween(15, 1200),
                    'verification_status'  => fake()->randomElement([VendorVerificationStatus::PENDING, VendorVerificationStatus::VERIFIED, VendorVerificationStatus::REJECTED]),
                ]
            );

            $index++;
        }
    }
}