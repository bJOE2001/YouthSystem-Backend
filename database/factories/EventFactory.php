<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'aip_reference_code' => 'AIP-'.$this->faker->randomNumber(4),
            'ppa_classification' => $this->faker->randomElement(['Program', 'Project', 'Activity']),
            'center_of_participation' => $this->faker->randomElement(['Education', 'Health', 'Active Citizenship']),
            'sustainable_development_goal' => 'SDG 1: No Poverty',
            'start_date' => clone $this->faker->dateTimeBetween('+1 week', '+1 month'),
            'end_date' => clone $this->faker->dateTimeBetween('+1 month', '+2 months'),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'location' => $this->faker->address(),
            'has_no_allocated_budget' => false,
            'budget_allocated' => 50000.00,
            'budget_utilized' => 0.00,
            'performance_indicator' => $this->faker->sentence(),
            'primary_objective_1' => $this->faker->sentence(),
            'primary_objective_2' => $this->faker->sentence(),
            'primary_objective_3' => $this->faker->sentence(),
            'status' => 'draft',
        ];
    }
}
