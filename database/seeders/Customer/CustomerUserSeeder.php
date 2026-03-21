<?php

namespace Database\Seeders\Customer;

use App\Enums\DefineStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make('password');
        $now      = now();

        foreach (range(1, 5) as $i) {
            User::updateOrCreate(
                ['email' => "customer{$i}@demo.test"],
                [
                    'name'              => "Customer {$i}",
                    'email'             => "customer{$i}@demo.test",
                    'phone'             => fake()->unique()->numerify('01#########'),
                    'password'          => $password,
                    'role'              => UserRole::CUSTOMER,
                    'status'            => fake()->randomElement(DefineStatus::values()),
                    'email_verified_at' => $now,
                    'phone_verified_at' => $now,
                ]
            );
        }
    }
}