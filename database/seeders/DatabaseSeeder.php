<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'username' => 'admin',
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('admin123'),
            'role' => 'admin',
            'status' => 'aktif',
            'email_verified_at' => now(),
            'created_by' => null,
            'last_login_at' => now(),
        ]);

        User::factory()->create([
            'username' => 'uptd',
            'name' => 'UPTD',
            'email' => 'uptd@gmail.com',
            'password' => bcrypt('uptd123'),
            'role' => 'uptd',
            'status' => 'aktif',
            'email_verified_at' => now(),
            'created_by' => null,
            'last_login_at' => now(),
        ]);

        User::factory()->create([
            'username' => 'admin_pangan',
            'name' => 'Admin Pangan',
            'email' => 'adminpangan@gmail.com',
            'password' => bcrypt('adminpangan123'),
            'role' => 'admin_pangan',
            'status' => 'aktif',
            'email_verified_at' => now(),
            'created_by' => null,
            'last_login_at' => now(),
        ]);

        User::factory()->create([
            'username' => 'admin_hartibun',
            'name' => 'Admin Hartibun',
            'email' => 'adminhartibun@gmail.com',
            'password' => bcrypt('adminhartibun123'),
            'role' => 'admin_hartibun',
            'status' => 'aktif',
            'email_verified_at' => now(),
            'created_by' => null,
            'last_login_at' => now(),
        ]);

        $this->call([
            KriteriaSeeder::class,
            AlamatWilayahSeeder::class,
            CpclSeeder::class,
        ]);

        
    }
}
