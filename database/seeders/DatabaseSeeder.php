<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::create([
            'username' => 'admin',
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'status' => 'aktif',
            'email_verified_at' => now(),
            'created_by' => null,
            'last_login_at' => now(),
        ]);

        User::create([
            'username' => 'uptd',
            'name' => 'UPTD',
            'email' => 'uptd@gmail.com',
            'password' => Hash::make('uptd123'),
            'role' => 'uptd',
            'status' => 'aktif',
            'email_verified_at' => now(),
            'created_by' => null,
            'last_login_at' => now(),
        ]);

        User::create([
            'username' => 'admin_pangan',
            'name' => 'Admin Pangan',
            'email' => 'adminpangan@gmail.com',
            'password' => Hash::make('adminpangan123'),
            'role' => 'admin_pangan',
            'status' => 'aktif',
            'email_verified_at' => now(),
            'created_by' => null,
            'last_login_at' => now(),
        ]);

        User::create([
            'username' => 'admin_hartibun',
            'name' => 'Admin Hartibun',
            'email' => 'adminhartibun@gmail.com',
            'password' => Hash::make('adminhartibun123'),
            'role' => 'admin_hartibun',
            'status' => 'aktif',
            'email_verified_at' => now(),
            'created_by' => null,
            'last_login_at' => now(),
        ]);
    }
}