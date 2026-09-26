<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Beneficiary;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CareRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'user_id' => User::factory(),
            'beneficiary_id' => Beneficiary::factory(),
            'service_id' => Service::factory(),
            'address_id' => Address::factory(),
            'scheduled_at' => fake()->dateTime(),
            'notes' => fake()->text(),
            'status' => fake()->word(),
            'address_label' => fake()->word(),
            'address_line' => fake()->word(),
            'city' => fake()->city(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'address_instructions' => fake()->text(),
            'cancelled_at' => fake()->dateTime(),
        ];
    }
}
