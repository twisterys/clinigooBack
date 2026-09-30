<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceCategory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'sort_order',
        'is_active',
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
            'name' => 'array',
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

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }
}
