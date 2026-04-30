<?php

namespace Database\Seeders\Customer;

use App\Enums\UserRole;
use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customerUsers = User::where('role', UserRole::CUSTOMER)->get();

        foreach ($customerUsers as $user) {
            CustomerProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'date_of_birth'  => fake()->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d'),
                    'wallet_balance' => fake()->randomFloat(2, 0, 350),
                    'loyalty_points' => fake()->numberBetween(0, 12500),
                ]
            );
        }
    }
}
