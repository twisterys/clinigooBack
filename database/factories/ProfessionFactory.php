<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Professions\Models\Profession;

/**
 * @extends Factory<Profession>
 */
class ProfessionFactory extends Factory
{
    protected $model = Profession::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->jobTitle(),
            'description' => fake()->sentence(),
            'slug' => fake()->slug(),
            'icon' => fake()->word(),
            'is_active' => true,
        ];
    }
}
