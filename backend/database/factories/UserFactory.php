<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'auth0_id' => 'auth0|' . $this->faker->unique()->uuid(),
            'email_verified_at' => now(),
            'roles' => ['agent'],
            'permissions' => [],
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'roles' => ['admin'],
            'permissions' => ['manage_users', 'manage_incidents', 'view_reports'],
        ]);
    }

    public function manager(): static
    {
        return $this->state(fn (array $attributes) => [
            'roles' => ['manager'],
            'permissions' => ['manage_incidents', 'view_reports'],
        ]);
    }

    public function agent(): static
    {
        return $this->state(fn (array $attributes) => [
            'roles' => ['agent'],
            'permissions' => ['view_incidents', 'update_incidents'],
        ]);
    }
}