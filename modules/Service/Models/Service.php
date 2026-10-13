<?php

namespace Modules\Service\Models;

use Database\Factories\ServiceFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\CareRequest\Models\CareRequest;
use Modules\Professions\Models\Profession;

/**
 * @property int $id
 * @property int $service_category_id
 * @property string $name
 * @property string|null $description
 * @property string|null $icon
 * @property string|null $base_price
 * @property int|null $duration_minutes
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ServiceCategory|null $serviceCategory
 * @property-read Collection<int, Profession> $professions
 */
class Service extends Model
{
    /** @use HasFactory<ServiceFactory> */
    use HasFactory;

    protected static function newFactory(): ServiceFactory
    {
        return ServiceFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'service_category_id',
        'name',
        'description',
        'icon',
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
            'base_price' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ServiceCategory, $this>
     */
    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    /**
     * @return HasMany<CareRequest, $this>
     */
    public function careRequests(): HasMany
    {
        return $this->hasMany(CareRequest::class);
    }

    /**
     * @return BelongsToMany<Profession, $this>
     */
    public function professions(): BelongsToMany
    {
        return $this->belongsToMany(Profession::class, 'profession_service')
            ->withTimestamps();
    }
}
