<?php

namespace Database\Factories;

use App\Models\SmsLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SmsLog>
 */
class SmsLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'recipient' => '09'.fake()->numerify('#########'),
            'message' => fake()->sentence(),
            'status' => 'success',
            'response_code' => '200',
            'response_body' => "Response: Success\r\nMessage: Commit successfully!\r\n",
            'event_type' => 'test_sms',
        ];
    }
}
