<?php

namespace Database\Factories;

use App\Models\ScholarPosition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScholarPosition>
 */
class ScholarPositionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->jobTitle(),
            'can_scan' => false,
            'status' => 'active',
        ];
    }

    public function canScan(): static
    {
        return $this->state(fn (array $attributes) => [
            'can_scan' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
