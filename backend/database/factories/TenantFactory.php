<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $companyName = $this->faker->company();
        
        return [
            'name' => $companyName,
            'subdomain' => $this->faker->unique()->slug(2),
            'config' => [
                'timezone' => $this->faker->timezone(),
                'date_format' => 'Y-m-d',
                'time_format' => 'H:i:s',
            ],
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}