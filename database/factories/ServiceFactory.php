<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceCategory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'service_category_id' => ServiceCategory::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'icon' => fake()->word(),
            'base_price' => fake()->randomFloat(2, 50, 500),
            'duration_minutes' => fake()->numberBetween(30, 120),
            'is_active' => fake()->boolean(),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
