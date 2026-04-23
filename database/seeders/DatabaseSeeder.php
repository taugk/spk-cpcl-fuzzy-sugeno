<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'username' => 'admin',
                'name' => 'Admin',
                'email' => 'admin@gmail.com',
                'password' => 'admin123',
                'role' => 'admin',
            ],
            [
                'username' => 'uptd',
                'name' => 'UPTD',
                'email' => 'uptd@gmail.com',
                'password' => 'uptd123',
                'role' => 'uptd',
            ],
            [
                'username' => 'admin_pangan',
                'name' => 'Admin Pangan',
                'email' => 'adminpangan@gmail.com',
                'password' => 'adminpangan123',
                'role' => 'admin_pangan',
            ],
            [
                'username' => 'admin_hartibun',
                'name' => 'Admin Hartibun',
                'email' => 'adminhartibun@gmail.com',
                'password' => 'adminhartibun123',
                'role' => 'admin_hartibun',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['username' => $user['username']], // kunci unik
                [
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'password' => Hash::make($user['password']),
                    'role' => $user['role'],
                    'status' => 'aktif',
                    'email_verified_at' => now(),
                    'created_by' => null,
                    'last_login_at' => now(),
                ]
            );
        }
    }
}