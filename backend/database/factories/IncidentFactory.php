<?php

namespace Database\Factories;

use App\Domains\Incident\Models\Incident;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class IncidentFactory extends Factory
{
    protected $model = Incident::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'title' => $this->faker->sentence(5),
            'description' => $this->faker->paragraph(3),
            'status' => $this->faker->randomElement([
                Incident::STATUS_OPEN,
                Incident::STATUS_IN_PROGRESS,
                Incident::STATUS_RESOLVED,
                Incident::STATUS_CLOSED,
            ]),
            'priority' => $this->faker->randomElement([
                Incident::PRIORITY_LOW,
                Incident::PRIORITY_MEDIUM,
                Incident::PRIORITY_HIGH,
                Incident::PRIORITY_CRITICAL,
            ]),
            'impact' => $this->faker->randomElement([
                Incident::IMPACT_LOW,
                Incident::IMPACT_MEDIUM,
                Incident::IMPACT_HIGH,
                Incident::IMPACT_ENTERPRISE,
            ]),
            'urgency' => $this->faker->randomElement(['low', 'medium', 'high', 'urgent']),
            'category' => $this->faker->randomElement(['Hardware', 'Software', 'Network', 'Security', 'Other']),
            'subcategory' => $this->faker->optional()->word(),
            'created_by' => User::factory(),
            'assigned_to' => $this->faker->optional()->randomElement([User::factory()]),
            'sla_deadline' => $this->faker->dateTimeBetween('now', '+3 days'),
            'tags' => $this->faker->optional()->words(3),
            'custom_fields' => [],
        ];
    }

    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Incident::STATUS_OPEN,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Incident::STATUS_IN_PROGRESS,
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Incident::STATUS_RESOLVED,
            'resolved_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'resolution_notes' => $this->faker->paragraph(),
        ]);
    }

    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => Incident::PRIORITY_CRITICAL,
            'impact' => Incident::IMPACT_ENTERPRISE,
            'urgency' => 'urgent',
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'sla_deadline' => $this->faker->dateTimeBetween('-2 days', '-1 hour'),
            'status' => $this->faker->randomElement([Incident::STATUS_OPEN, Incident::STATUS_IN_PROGRESS]),
        ]);
    }
}