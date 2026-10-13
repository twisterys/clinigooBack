<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Address\Models\Address;
use Modules\Beneficiary\Models\Beneficiary;
use Modules\CareRequest\Models\CareRequest;
use Modules\Service\Models\Service;
use Modules\User\Models\User;

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
            'reference' => 'CLG-'.fake()->numberBetween(1000, 9999),
            'user_id' => User::factory(),
            'beneficiary_id' => Beneficiary::factory(),
            'service_id' => Service::factory(),
            'address_id' => Address::factory(),
            'urgent' => false,
            'date' => fake()->date(),
            'time' => '14:30',
            'scheduled_at' => fake()->dateTime(),
            'duration_hours' => fake()->randomFloat(2, 1, 8),
            'description' => fake()->text(),
            'notes' => fake()->text(),
            'perimeter' => fake()->numberBetween(5, 50),
            'evaluation' => fake()->randomFloat(2, 3, 5),
            'gender' => fake()->randomElement(['all', 'male', 'female']),
            'certified' => fake()->boolean(),
            'free_mobility' => fake()->boolean(),
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
