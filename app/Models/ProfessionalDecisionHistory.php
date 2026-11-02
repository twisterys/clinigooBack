<?php

namespace App\Models;

use App\Enums\ProfessionalHistoryAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $professional_profile_id
 * @property int|null $admin_id
 * @property ProfessionalHistoryAction $action
 * @property string|null $status_before
 * @property string|null $status_after
 * @property string|null $reason
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ProfessionalProfile $professionalProfile
 * @property-read User|null $admin
 */
class ProfessionalDecisionHistory extends Model
{
    use HasFactory;

    protected $table = 'professional_decision_histories';

    protected $fillable = [
        'professional_profile_id',
        'admin_id',
        'action',
        'status_before',
        'status_after',
        'reason',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => ProfessionalHistoryAction::class,
            'metadata' => 'array',
        ];
    }

    public function professionalProfile(): BelongsTo
    {
        return $this->belongsTo(ProfessionalProfile::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
