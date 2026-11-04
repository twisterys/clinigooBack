<?php

namespace App\Models;

use Database\Factories\CareRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CareRequest extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return CareRequestFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (CareRequest $careRequest) {
            if (empty($careRequest->uuid)) {
                $careRequest->uuid = (string) Str::uuid();
            }

            if (empty($careRequest->reference)) {
                $careRequest->reference = static::generateUniqueReference();
            }
        });
    }

    /**
     * Generate a unique reference for a care request.
     */
    public static function generateUniqueReference(): string
    {
        do {
            $reference = 'CLG-' . strtoupper(Str::random(8));
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid',
        'reference',
        'user_id',
        'beneficiary_id',
        'service_id',
        'address_id',
        'urgent',
        'date',
        'time',
        'scheduled_at',
        'description',
        'status',
        'address_label',
        'address_line',
        'city',
        'latitude',
        'longitude',
        'address_instructions',
        'cancelled_at',
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
            'user_id' => 'integer',
            'beneficiary_id' => 'integer',
            'service_id' => 'integer',
            'address_id' => 'integer',
            'urgent' => 'boolean',
            'date' => 'date:Y-m-d',
            'scheduled_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Backward-compatibility accessor for notes.
     */
    public function getNotesAttribute(): ?string
    {
        return $this->description;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CareRequestDocument::class);
    }
}
