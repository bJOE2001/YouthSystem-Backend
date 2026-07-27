<?php

namespace Database\Factories;

use App\Models\LydcMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LydcMember>
 */
class LydcMemberFactory extends Factory
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
            'position' => fake()->randomElement(['Chairperson', 'Vice Chairperson', 'Secretary', 'Treasurer', 'Council Member']),
            'organization' => fake()->company(),
            'sector' => fake()->word(),
            'responsibilities' => fake()->paragraph(),
            'status' => 'Active',
        ];
    }
}
