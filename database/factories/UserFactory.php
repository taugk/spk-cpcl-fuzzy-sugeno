<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Password cache.
     */
    protected static ?string $password;

    /**
     * Define default state.
     */
    public function definition(): array
    {
        return [
            'username' => $this->faker->username(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),

            // default values (akan dioverride oleh seeder kamu)
            'role' => 'admin',
            'status' => 'aktif',
            'created_by' => null,
            'last_login_at' => null,
        ];
    }

    /**
     * Unverified state.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}