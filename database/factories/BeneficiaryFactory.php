<?php

namespace Database\Factories;

use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BeneficiaryFactory extends Factory
{
    protected $model = Beneficiary::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'relationship' => fake()->word(),
            'gender' => fake()->word(),
            'date_of_birth' => fake()->date(),
            'phone' => fake()->phoneNumber(),
            'medical_notes' => fake()->text(),
            'is_default' => fake()->boolean(),
        ];
    }
}
