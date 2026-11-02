<?php

namespace App\Models;

use Database\Factories\ProfessionFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $slug
 * @property string|null $icon
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Service> $services
 * @property-read Collection<int, ProfessionDocument> $documents
 */
class Profession extends Model
{
    /** @use HasFactory<ProfessionFactory> */
    use HasFactory;

    protected $table = 'professions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'slug',
        'icon',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function newFactory(): ProfessionFactory
    {
        return ProfessionFactory::new();
    }

    /**
     * Services associated with this profession.
     *
     * @return BelongsToMany<Service, $this>
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'profession_service')
            ->withTimestamps();
    }

    /**
     * Documents required or optional for this profession.
     *
     * @return BelongsToMany<ProfessionDocument, $this>
     */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(ProfessionDocument::class, 'profession_profession_document')
            ->withPivot('required')
            ->withTimestamps();
    }

    /**
     * Get only strictly required documents for this profession.
     *
     * @return BelongsToMany<ProfessionDocument, $this>
     */
    public function requiredDocuments(): BelongsToMany
    {
        return $this->documents()->wherePivot('required', true);
    }
}
