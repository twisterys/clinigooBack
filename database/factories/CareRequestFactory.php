<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Beneficiary;
use App\Models\CareRequest;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CareRequestFactory extends Factory
{
    protected $model = CareRequest::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'reference' => CareRequest::generateUniqueReference(),
            'user_id' => User::factory(),
            'beneficiary_id' => Beneficiary::factory(),
            'service_id' => Service::factory(),
            'address_id' => Address::factory(),
            'urgent' => false,
            'date' => fake()->date(),
            'time' => '14:30',
            'scheduled_at' => fake()->dateTime(),
            'description' => fake()->text(),
            'status' => 'pending',
            'address_label' => fake()->word(),
            'address_line' => fake()->streetAddress(),
            'city' => fake()->city(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'address_instructions' => fake()->text(),
            'cancelled_at' => null,
        ];
    }
}
