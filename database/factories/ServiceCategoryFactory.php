<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Service\Models\ServiceCategory;

class ServiceCategoryFactory extends Factory
{
    protected $model = ServiceCategory::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'icon' => fake()->word(),
            'is_active' => fake()->boolean(),
        ];
    }
}
