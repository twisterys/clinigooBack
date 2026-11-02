<?php

namespace Database\Factories;

use App\Enums\DocumentVerificationStatus;
use App\Models\ProfessionalDocument;
use App\Models\ProfessionalProfile;
use App\Models\ProfessionDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProfessionalDocument>
 */
class ProfessionalDocumentFactory extends Factory
{
    protected $model = ProfessionalDocument::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'professional_profile_id' => ProfessionalProfile::factory(),
            'profession_document_id' => ProfessionDocument::factory(),
            'file_path' => 'professional_documents/test/document.pdf',
            'original_name' => 'document.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 102400,
            'status' => DocumentVerificationStatus::Pending,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DocumentVerificationStatus::Pending,
        ]);
    }

    public function validated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DocumentVerificationStatus::Validated,
            'verified_at' => now(),
        ]);
    }

    public function rejected(?string $reason = 'Document illisible'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DocumentVerificationStatus::Rejected,
            'rejection_reason' => $reason,
            'verified_at' => now(),
        ]);
    }
}
