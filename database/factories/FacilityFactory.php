<?php

namespace Database\Factories;

use App\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Facility>
 */
class FacilityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Court',
            'type' => fake()->randomElement(['Basketball Court', 'Multi-Purpose Hall', 'Tennis Court']),
            'location' => fake()->address(),
            'available_time' => '8:00 AM - 10:00 PM',
            'status' => fake()->randomElement(['Active', 'Inactive', 'Under Construction']),
            'image' => null,
        ];
    }
}
