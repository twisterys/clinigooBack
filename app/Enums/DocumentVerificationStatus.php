<?php

namespace App\Enums;

enum DocumentVerificationStatus: string
{
    case Pending = 'pending';
    case Validated = 'validated';
    case Rejected = 'rejected';

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
            self::Pending => 'En attente',
            self::Validated => 'Validé',
            self::Rejected => 'Rejeté',
        };
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
}
