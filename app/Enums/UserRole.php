<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case SuperAdmin = 'super_admin';
    case Patient = 'patient';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Clinic admin',
            self::SuperAdmin => 'Super admin',
            self::Patient => 'Patient',
        };
    }

    /** Roles that can access the /admin area. */
    public static function staff(): array
    {
        return [self::Admin, self::SuperAdmin];
    }
}
