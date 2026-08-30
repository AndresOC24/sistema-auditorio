<?php

namespace Database\Factories;

use App\Models\RequestingArea;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RequestingArea>
 */
class RequestingAreaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->word()),
            'is_active' => true,
        ];
    }

    /**
     * Catálogo retirado: sigue existiendo para el histórico pero ya no se
     * ofrece al capturar eventos nuevos.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
