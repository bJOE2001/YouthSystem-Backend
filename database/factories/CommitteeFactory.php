<?php

namespace Database\Factories;

use App\Models\Committee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Committee>
 */
class CommitteeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Committee on '.fake()->unique()->word();

        return [
            'name' => $name,
            'description' => fake()->sentence(),
            'code' => Str::slug($name, '_'),
            'status' => 'active',
        ];
    }
}
