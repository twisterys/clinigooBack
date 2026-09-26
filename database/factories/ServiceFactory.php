<?php

namespace Database\Factories;

use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'service_category_id' => ServiceCategory::factory(),
            'name' => '{}',
            'slug' => fake()->slug(),
            'short_description' => '{}',
            'description' => '{}',
            'icon' => fake()->word(),
            'image' => fake()->word(),
            'base_price' => fake()->randomFloat(2, 0, 99999999.99),
            'duration_minutes' => fake()->numberBetween(-10000, 10000),
            'is_active' => fake()->boolean(),
            'sort_order' => fake()->numberBetween(-10000, 10000),
        ];
    }
}
