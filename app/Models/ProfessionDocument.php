<?php

namespace App\Models;

use Database\Factories\ProfessionDocumentFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property array<string>|null $formats
 * @property array<string>|null $enabled_formats
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Profession> $professions
 */
class ProfessionDocument extends Model
{
    /** @use HasFactory<ProfessionDocumentFactory> */
    use HasFactory;

    protected $table = 'profession_documents';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'formats',
        'enabled_formats',
        'is_active',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'enabled_formats',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'formats' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function newFactory(): ProfessionDocumentFactory
    {
        return ProfessionDocumentFactory::new();
    }

    /**
     * Alias accessor and mutator for enabled_formats matching formats.
     *
     * @return Attribute<array<string>|null, array<string>|string|null>
     */
    protected function enabledFormats(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value, array $attributes) => isset($attributes['formats'])
                ? json_decode((string) $attributes['formats'], true)
                : ($this->formats ?? []),
            set: function ($value) {
                $normalized = is_array($value)
                    ? array_values(array_filter(array_map('trim', $value)))
                    : (is_string($value) ? array_values(array_filter(array_map('trim', explode(',', $value)))) : []);

                return ['formats' => json_encode($normalized)];
            }
        );
    }

    /**
     * Check if a specific file format/extension is allowed.
     */
    public function isFormatAllowed(string $extension): bool
    {
        $formats = $this->formats ?? ['pdf', 'jpg', 'jpeg', 'png'];

        return in_array(strtolower($extension), array_map('strtolower', $formats), true);
    }

    /**
     * Professions that require or include this document.
     *
     * @return BelongsToMany<Profession, $this>
     */
    public function professions(): BelongsToMany
    {
        return $this->belongsToMany(Profession::class, 'profession_profession_document')
            ->withPivot('required')
            ->withTimestamps();
    }
}
