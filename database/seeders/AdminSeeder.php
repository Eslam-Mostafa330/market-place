<?php

namespace Database\Seeders;

use App\Enums\DefineStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'               => 'Admin',
                'phone'              => '90123456789',
                'email_verified_at'  => now(),
                'role'               => UserRole::ADMIN,
                'status'             => DefineStatus::ACTIVE,
                'password'           => Hash::make('password'),
                'remember_token'     => Str::random(10),
            ]
        );
    }
}
