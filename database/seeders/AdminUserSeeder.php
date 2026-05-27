<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()
            ->where('username', 'admin')
            ->orWhere('email', 'admin@ricksite.com')
            ->first();

        $attributes = [
            'name' => 'Admin Ricksite',
            'username' => 'admin',
            'email' => 'admin@ricksite.com',
            'password' => Hash::make('password'),
        ];

        $admin ? $admin->update($attributes) : User::create($attributes);
    }
}
