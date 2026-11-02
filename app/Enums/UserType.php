<?php

namespace App\Enums;

enum UserType: string
{
    case Patient = 'patient';
    case Professional = 'professional';
    case Admin = 'admin';

    /**
     * Get all enum values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get a human-readable label for the user type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Patient => 'Patient',
            self::Professional => 'Professionnel',
            self::Admin => 'Administrateur',
        };
    }
}
