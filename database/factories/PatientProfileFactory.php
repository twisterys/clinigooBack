<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\User\Models\PatientProfile;
use Modules\User\Models\User;

/**
 * @extends Factory<PatientProfile>
 */
class PatientProfileFactory extends Factory
{
    protected $model = PatientProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->patient(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'birthday' => fake()->date('Y-m-d', '-18 years'),
            'sexe' => fake()->randomElement(['M', 'F']),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
        ];
    }
}
