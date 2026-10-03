<?php

namespace Database\Factories;

use App\Enums\RoleName;
use App\Enums\UserStatus;
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
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->bothify('user####??'),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'status' => UserStatus::ACTIVE,
            'remember_token' => Str::random(10),
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

    public function withStatus(UserStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }

    /**
     * A student account: username is the student code and there is no email.
     */
    public function student(): static
    {
        return $this->state(fn (array $attributes) => [
            'username' => fake()->unique()->numerify('st#####'),
            'email' => null,
            'email_verified_at' => null,
        ])->withRole(RoleName::STUDENT);
    }

    public function counselor(): static
    {
        return $this->withRole(RoleName::COUNSELOR);
    }

    public function admin(): static
    {
        return $this->withRole(RoleName::ADMIN);
    }

    public function withRole(RoleName $role): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole($role->value));
    }
}
