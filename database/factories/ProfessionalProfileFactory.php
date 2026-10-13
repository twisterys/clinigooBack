<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Professions\Models\Profession;
use Modules\User\Enums\ProfessionalStatus;
use Modules\User\Models\ProfessionalProfile;
use Modules\User\Models\User;

/**
 * @extends Factory<ProfessionalProfile>
 */
class ProfessionalProfileFactory extends Factory
{
    protected $model = ProfessionalProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->professional(),
            'profession_id' => Profession::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'sexe' => fake()->randomElement(['M', 'F']),
            'id_number' => 'PRO-'.fake()->unique()->numerify('#####'),
            'status' => ProfessionalStatus::Draft,
            'is_available' => false,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProfessionalStatus::Draft,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProfessionalStatus::Pending,
            'submitted_at' => now(),
        ]);
    }

    public function validated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProfessionalStatus::Validated,
            'submitted_at' => now()->subDay(),
            'validated_at' => now(),
            'is_available' => true,
        ]);
    }

    public function rejected(?string $reason = 'Documents non conformes'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProfessionalStatus::Rejected,
            'submitted_at' => now()->subDay(),
            'rejection_reason' => $reason,
        ]);
    }

    public function pendingChanges(array $pendingData = []): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProfessionalStatus::PendingChanges,
            'pending_data' => $pendingData,
        ]);
    }

    public function available(bool $available = true): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => $available,
        ]);
    }
}
