<?php

namespace App\Models;

use App\Enums\DocumentVerificationStatus;
use Database\Factories\ProfessionalDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $professional_profile_id
 * @property int $profession_document_id
 * @property string $file_path
 * @property string $original_name
 * @property string|null $mime_type
 * @property int|null $file_size
 * @property DocumentVerificationStatus $status
 * @property string|null $rejection_reason
 * @property Carbon|null $verified_at
 * @property int|null $verified_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $url
 * @property-read ProfessionalProfile $professionalProfile
 * @property-read ProfessionDocument $professionDocument
 * @property-read User|null $verifier
 */
class ProfessionalDocument extends Model
{
    /** @use HasFactory<ProfessionalDocumentFactory> */
    use HasFactory;

    protected $table = 'professional_documents';

    protected $fillable = [
        'professional_profile_id',
        'profession_document_id',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'status',
        'rejection_reason',
        'verified_at',
        'verified_by',
    ];

    protected $appends = [
        'url',
    ];

    protected static function newFactory(): ProfessionalDocumentFactory
    {
        return ProfessionalDocumentFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DocumentVerificationStatus::class,
            'verified_at' => 'datetime',
            'file_size' => 'integer',
        ];
    }

    public function professionalProfile(): BelongsTo
    {
        return $this->belongsTo(ProfessionalProfile::class);
    }

    public function professionDocument(): BelongsTo
    {
        return $this->belongsTo(ProfessionDocument::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    public function isValidated(): bool
    {
        return $this->status === DocumentVerificationStatus::Validated;
    }

    public function isPending(): bool
    {
        return $this->status === DocumentVerificationStatus::Pending;
    }

    public function isRejected(): bool
    {
        return $this->status === DocumentVerificationStatus::Rejected;
    }
}
