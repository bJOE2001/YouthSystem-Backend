<?php

namespace Database\Factories;

use App\Models\SportsProgram;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SportsProgram>
 */
class SportsProgramFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'type' => fake()->randomElement(['Program', 'Project', 'Activity']),
            'strategic_direction' => fake()->sentence(),
            'start_date' => fake()->date(),
            'end_date' => fake()->date(),
            'start_time' => fake()->time('H:i'),
            'location' => fake()->city(),
            'budget_allocated' => fake()->randomFloat(2, 1000, 50000),
            'budget_utilized' => fake()->randomFloat(2, 0, 1000),
            'objective_1' => fake()->paragraph(),
            'objective_2' => fake()->paragraph(),
            'objective_3' => fake()->paragraph(),
            'status' => fake()->randomElement(['Draft', 'Upcoming', 'Ongoing', 'Completed', 'Cancelled']),
        ];
    }
}
