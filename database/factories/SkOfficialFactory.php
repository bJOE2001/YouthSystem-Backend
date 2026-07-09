<?php

namespace Database\Factories;

use App\Models\SkOfficial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SkOfficial>
 */
class SkOfficialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'initials' => strtoupper(fake()->lexify('??')),
            'barangay' => fake()->streetName(),
            'contact' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'committee' => fake()->randomElement(['Sports', 'Education', 'Health', 'Environment', 'Finance']),
            'position' => fake()->jobTitle(),
            'responsibilities' => fake()->paragraph(),
            'term' => '2023 - 2025',
        ];
    }
}
