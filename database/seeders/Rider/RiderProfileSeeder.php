<?php

namespace Database\Seeders\Rider;

use App\Enums\RiderAvailability;
use App\Enums\UserRole;
use App\Models\RiderProfile;
use App\Models\User;
use Illuminate\Database\Seeder;


class RiderProfileSeeder extends Seeder
{
    public function run(): void
    {
        $riderUsers = User::where('role', UserRole::RIDER)->get();

        foreach ($riderUsers as $user) {
            RiderProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'license_number'     => 'RDR-' . fake()->unique()->numerify('######-###'),
                    'license_expiry'     => fake()->dateTimeBetween('+6 months', '+36 months')->format('Y-m-d'),
                    'vehicle_type'       => fake()->randomElement(['motorcycle', 'bicycle', 'scooter', 'car']),
                    'vehicle_number'     => strtoupper(fake()->bothify('??###??')),
                    'rider_availability' => fake()->randomElement(RiderAvailability::values()),
                    'current_latitude'   => fake()->randomFloat(8, 29.95, 30.15),
                    'current_longitude'  => fake()->randomFloat(8, 31.20, 31.40),
                    'total_deliveries'   => fake()->numberBetween(40, 3800),
                ]
            );
        }
    }
}