<?php

namespace App\Models;

use Database\Factories\PatientProfileFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $first_name
 * @property string $last_name
 * @property Carbon|null $birthday
 * @property string|null $sexe
 * @property string|null $phone
 * @property string|null $email
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $full_name
 * @property-read User $user
 */
class PatientProfile extends Model
{
    /** @use HasFactory<PatientProfileFactory> */
    use HasFactory;

    protected $table = 'patient_profiles';

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'birthday',
        'sexe',
        'phone',
        'email',
        // Also allow camelCase on mass assignment
        'firstName',
        'lastName',
    ];

    /**
     * @var array<string>
     */
    protected $appends = [
        'first_name',
        'last_name',
        'full_name',
    ];

    protected static function newFactory(): PatientProfileFactory
    {
        return PatientProfileFactory::new();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birthday' => 'date',
        ];
    }

    /**
     * User relationship.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * CamelCase alias accessor & mutator for firstName.
     */
    protected function firstName(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value, array $attributes) => $attributes['first_name'] ?? $value,
            set: fn (?string $value) => ['first_name' => $value],
        );
    }

    /**
     * CamelCase alias accessor & mutator for lastName.
     */
    protected function lastName(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value, array $attributes) => $attributes['last_name'] ?? $value,
            set: fn (?string $value) => ['last_name' => $value],
        );
    }

    /**
     * Full name accessor.
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => trim(($this->first_name ?? '').' '.($this->last_name ?? '')),
        );
    }
}
