<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email    = 'admin@parfumeria.hu';
        $password = env('SEED_ADMIN_PASSWORD', 'Admin1234!');

        User::updateOrCreate(
            ['email' => $email],
            [
                'name'              => 'Admin',
                'password'          => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        Admin::updateOrCreate(
            ['email' => $email],
            [
                'name'     => 'Admin',
                'password' => Hash::make($password),
                'role'     => 'admin',
            ]
        );
    }
}
