<?php

namespace Database\Seeders\Vendor;

use App\Enums\DefineStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class VendorUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make('password');
        $now      = now();

        foreach (range(1, 10) as $i) {
            User::updateOrCreate(
                ['email' => "vendor{$i}@demo.test"],
                [
                    'name'              => "Vendor {$i}",
                    'email'             => "vendor{$i}@demo.test",
                    'phone'             => fake()->unique()->numerify('01#########'),
                    'password'          => $password,
                    'role'              => UserRole::VENDOR,
                    'status'            => fake()->randomElement(DefineStatus::values()),
                    'email_verified_at' => $now,
                    'phone_verified_at' => $now,
                ]
            );
        }
    }
}