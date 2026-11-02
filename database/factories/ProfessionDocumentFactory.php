<?php

namespace Database\Factories;

use App\Models\ProfessionDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProfessionDocument>
 */
class ProfessionDocumentFactory extends Factory
{
    protected $model = ProfessionDocument::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'formats' => ['pdf', 'jpg', 'jpeg', 'png'],
            'is_active' => true,
        ];
    }
}
