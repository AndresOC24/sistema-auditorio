<?php

namespace Database\Factories;

use App\Enums\ItPersonRole;
use App\Models\ItPerson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItPerson>
 */
class ItPersonFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->name(),
            'role' => fake()->randomElement(ItPersonRole::cases()),
            'is_active' => true,
        ];
    }

    public function administrativo(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => ItPersonRole::Administrativo,
        ]);
    }

    public function becario(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => ItPersonRole::Becario,
        ]);
    }

    /**
     * Persona que ya no está en el equipo. Sus eventos pasados la conservan.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
