<?php

namespace Database\Factories;

use App\Models\Purok;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purok>
 */
class PurokFactory extends Factory
{
    protected $model = Purok::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'barangay' => 'Apokon',
            'barangay_id' => null,
            'name' => 'Purok '.fake()->unique()->numberBetween(1, 9999),
            'user_id' => null,
        ];
    }
}
