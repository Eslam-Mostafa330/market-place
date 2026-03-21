<?php

namespace Database\Seeders\Admin;

use App\Enums\DefineStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make('password');
        $now      = now();

        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'              => 'Admin',
                'phone'             => '01234567890',
                'role'              => UserRole::ADMIN,
                'status'            => DefineStatus::ACTIVE,
                'password'          => $password,
                'email_verified_at' => $now,
                'phone_verified_at' => $now,
            ]
        );
    }
}