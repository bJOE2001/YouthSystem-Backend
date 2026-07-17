<?php

namespace Database\Factories;

use App\Models\BookingRequest;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingRequest>
 */
class BookingRequestFactory extends Factory
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
            'facility_id' => Facility::factory(),
            'date' => fake()->date(),
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'purpose' => fake()->sentence(),
            'status' => fake()->randomElement(['Pending', 'Approved', 'Declined', 'Cancelled']),
        ];
    }
}
