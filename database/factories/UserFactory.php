<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
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
        $hasFake = function_exists('fake');

        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => 1,
            'username' => $hasFake ? fake()->unique()->userName() : 'user_' . Str::random(5),
            'full_name' => $hasFake ? fake()->name() : 'User ' . Str::random(5),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'Cashier',
            'permissions' => ['register', 'orders', 'customers'],
            'shift_schedule' => 'Flexible / Full Day',
            'max_cash_limit' => 1000.00,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
