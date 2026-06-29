<?php

namespace Database\Factories;

use App\Enums\YouthProfileStatus;
use App\Models\User;
use App\Models\YouthProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<YouthProfile>
 */
class YouthProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->youth()->active(),
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->optional()->firstName(),
            'last_name' => fake()->lastName(),
            'suffix' => fake()->optional()->randomElement(['Jr.', 'Sr.', 'III']),
            'gender' => fake()->randomElement(['Male', 'Female', 'Prefer not to say']),
            'birth_date' => fake()->dateTimeBetween('-30 years', '-15 years')->format('Y-m-d'),
            'place_of_birth' => fake()->city(),
            'mobile_number' => '09'.fake()->numerify('#########'),
            'father_first_name' => fake()->optional()->firstNameMale(),
            'father_middle_name' => fake()->optional()->firstNameMale(),
            'father_last_name' => fake()->optional()->lastName(),
            'mother_first_name' => fake()->optional()->firstNameFemale(),
            'mother_middle_name' => fake()->optional()->firstNameFemale(),
            'mother_last_name' => fake()->optional()->lastName(),
            'parents_contact_number' => fake()->optional()->numerify('09#########'),
            'guardian_first_name' => fake()->optional()->firstName(),
            'guardian_last_name' => fake()->optional()->lastName(),
            'guardian_contact_number' => fake()->optional()->numerify('09#########'),
            'currently_attending_school' => fake()->boolean(),
            'senior_high_graduate' => fake()->boolean(),
            'educational_attainment' => fake()->randomElement([
                'High School Graduate',
                'Senior High School Graduate',
                'College Level',
                'College Graduate',
            ]),
            'course_strand' => fake()->optional()->words(3, true),
            'ethnicity' => fake()->optional()->randomElement(['Tagalog', 'Cebuano', 'Mandaya', 'Mansaka']),
            'religious_affiliation' => fake()->optional()->word(),
            'has_disability' => fake()->boolean(10),
            'overseas_worker' => fake()->boolean(5),
            'lgbtq_member' => fake()->boolean(10),
            'special_youth_sector' => fake()->optional()->randomElement([
                'Out-of-school Youth',
                'Working Youth',
                'Youth with Special Needs',
                'Indigenous People Youth',
            ]),
            'attached_id_path' => null,
            'birth_registered' => fake()->boolean(95),
            'civil_status' => fake()->randomElement(['Single', 'Married', 'Widowed', 'Separated']),
            'solo_parent' => fake()->boolean(5),
            'barangay' => fake()->citySuffix(),
            'purok_sitio' => fake()->streetName(),
            'city' => 'Tagum City',
            'province' => 'Davao del Norte',
            'postal_code' => '8100',
            'status' => YouthProfileStatus::Pending->value,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => YouthProfileStatus::Approved->value,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => YouthProfileStatus::Rejected->value,
            'reviewed_at' => now(),
            'rejection_reason' => 'The submitted identification could not be verified.',
        ]);
    }
}
