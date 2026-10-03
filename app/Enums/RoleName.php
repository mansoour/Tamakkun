<?php

namespace App\Enums;

/**
 * Roles group permissions together. Code must not check role names to
 * authorize actions — check a PermissionName instead.
 */
enum RoleName: string
{
    case STUDENT = 'student';
    case COUNSELOR = 'counselor';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::STUDENT => 'الطالبة',
            self::COUNSELOR => 'الموجهة الطلابية',
            self::ADMIN => 'مدير النظام',
        };
    }
}
