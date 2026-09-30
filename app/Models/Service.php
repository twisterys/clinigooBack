<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'service_category_id',
        'name',
        'slug',
        'short_description',
        'description',
        'icon',
        'image',
        'base_price',
        'duration_minutes',
        'is_active',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'service_category_id' => 'integer',
            'name' => 'array',
            'short_description' => 'array',
            'description' => 'array',
            'base_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get translated name for current locale or request header.
     */
    public function getTranslatedName(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $name = $this->name;

        if (is_array($name)) {
            return $name[$locale] ?? $name['fr'] ?? $name['en'] ?? reset($name) ?: '';
        }

        return (string) $name;
    }

    /**
     * Get translated description for current locale or request header.
     */
    public function getTranslatedDescription(?string $locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        $desc = $this->short_description ?? $this->description;

        if (is_array($desc)) {
            return $desc[$locale] ?? $desc['fr'] ?? $desc['en'] ?? reset($desc) ?: '';
        }

        return (string) $desc;
    }

    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    public function careRequests(): HasMany
    {
        return $this->hasMany(CareRequest::class);
    }
}
