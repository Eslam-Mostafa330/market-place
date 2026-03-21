<?php

namespace Database\Seeders\Customer;

use App\Enums\AddressType;
use App\Enums\BooleanStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Database\Seeder;

class UserAddressSeeder extends Seeder
{
    public function run(): void
    {
        $customerUsers = User::where('role', UserRole::CUSTOMER)->get();

        $locations = [
            'Cairo Governorate' => [
                'Nasr City', 'Heliopolis', 'Maadi', 'New Cairo', 'Mokattam',
                'Abbasiya', 'Downtown Cairo', 'Zamalek', 'Garden City', 'Sayeda Zeinab',
            ],
            'Giza Governorate' => [
                'Giza', '6th of October City', 'Sheikh Zayed City', 'Imbaba', 'Dokki', 'Faisal',
            ],
            'Alexandria Governorate' => [
                'Alexandria', 'Montaza', 'Smouha', 'Miami', 'Sporting', 'Roshdy',
            ],
        ];

        foreach ($customerUsers as $user) {
            $addressCount = fake()->numberBetween(1, 3);

            for ($i = 0; $i < $addressCount; $i++) {
                $isDefault = ($i === 0) ? BooleanStatus::YES : BooleanStatus::NO;
                $governorate = fake()->randomElement(array_keys($locations));
                $city = fake()->randomElement($locations[$governorate]);

                UserAddress::create([
                    'user_id'          => $user->id,
                    'country'          => 'Egypt',
                    'city'             => $city,
                    'state'            => $governorate,
                    'postal_code'      => fake()->optional(0.7)->numerify('11###'),
                    'address_line_1'   => fake()->streetAddress(),
                    'address_line_2'   => fake()->optional(0.4)->secondaryAddress(),
                    'contact_phone'    => fake()->optional(0.5)->numerify('05#########'),
                    'additional_phone' => fake()->optional(0.5)->numerify('06#########'),
                    'additional_info'  => fake()->optional(0.3)->sentence(5),
                    'latitude'         => fake()->randomFloat(8, 29.75, 30.35),
                    'longitude'        => fake()->randomFloat(8, 30.95, 31.55),
                    'is_default'       => $isDefault,
                    'type'             => fake()->randomElement(AddressType::values()),
                ]);
            }
        }
    }
}