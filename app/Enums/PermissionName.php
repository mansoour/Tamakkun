<?php

namespace App\Enums;

/**
 * Every permission the application checks. Authorization in routes,
 * policies and views must use these permissions, never role names.
 *
 * New permissions are added to the database by a migration (see
 * docs/roles-permissions.md), never by re-running a seeder.
 */
enum PermissionName: string
{
    case ACCESS_STUDENT_AREA = 'student-area.access';
    case ACCESS_COUNSELOR_AREA = 'counselor-area.access';
    case ACCESS_ADMIN_AREA = 'admin-area.access';

    public function label(): string
    {
        return match ($this) {
            self::ACCESS_STUDENT_AREA => 'الدخول إلى مساحة الطالبة',
            self::ACCESS_COUNSELOR_AREA => 'الدخول إلى لوحة الموجهة الطلابية',
            self::ACCESS_ADMIN_AREA => 'الدخول إلى لوحة الإدارة',
        };
    }
}
