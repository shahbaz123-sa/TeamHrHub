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
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'avatar' => null,
            'role' => fake()->randomElement(['admin', 'author', 'editor', 'maintainer', 'subscriber']),
            'plan' => fake()->randomElement(['basic', 'company', 'enterprise', 'team']),
            'status' => fake()->randomElement(['pending', 'active', 'inactive']),
            'billing_email' => fake()->optional()->safeEmail(),
            'company' => fake()->optional()->company(),
            'tax_id' => fake()->optional()->regexify('[A-Z]{2}[0-9]{8}'),
            'phone' => fake()->optional()->phoneNumber(),
            'address' => fake()->optional()->streetAddress(),
            'city' => fake()->optional()->city(),
            'state' => fake()->optional()->state(),
            'country' => fake()->optional()->country(),
            'postal_code' => fake()->optional()->postcode(),
            'last_login_at' => fake()->optional()->dateTimeBetween('-1 year', 'now'),
            'last_login_ip' => fake()->optional()->ipv4(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn(array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user should have admin role.
     */
    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'admin',
        ]);
    }

    /**
     * Indicate that the user should be active.
     */
    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the user should have enterprise plan.
     */
    public function enterprise(): static
    {
        return $this->state(fn(array $attributes) => [
            'plan' => 'enterprise',
        ]);
    }
}