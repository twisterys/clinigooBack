<?php

namespace Modules\User\Enums;

enum ProfessionalStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Validated = 'validated';
    case Rejected = 'rejected';
    case PendingChanges = 'pending_changes';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon / Incomplet',
            self::Pending => 'En attente de vérification',
            self::Validated => 'Validé',
            self::Rejected => 'Rejeté / Correction demandée',
            self::PendingChanges => 'Modifications en attente',
        };
    }

    public function isDraft(): bool
    {
        return $this === self::Draft;
    }

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    public function isValidated(): bool
    {
        return $this === self::Validated;
    }

    public function isRejected(): bool
    {
        return $this === self::Rejected;
    }

    public function isPendingChanges(): bool
    {
        return $this === self::PendingChanges;
    }
}
