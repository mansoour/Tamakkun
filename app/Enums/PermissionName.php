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
    case MANAGE_SCHOOLS = 'schools.manage';
    case MANAGE_USERS = 'users.manage';
    case VIEW_ALL_STUDENTS = 'students.view-all';
    case VIEW_ASSIGNED_STUDENTS = 'students.view-assigned';
    case IMPORT_STUDENTS = 'students.import';
    case MANAGE_SETTINGS = 'settings.manage';
    case MANAGE_CONTENT = 'content.manage';
    case MANAGE_LINKS = 'links.manage';

    public function label(): string
    {
        return match ($this) {
            self::ACCESS_STUDENT_AREA => 'الدخول إلى مساحة الطالبة',
            self::ACCESS_COUNSELOR_AREA => 'الدخول إلى لوحة الموجهة الطلابية',
            self::ACCESS_ADMIN_AREA => 'الدخول إلى لوحة الإدارة',
            self::MANAGE_SCHOOLS => 'إدارة المدارس والأعوام الدراسية والصفوف والفصول',
            self::MANAGE_USERS => 'إدارة حسابات الطالبات والموجهات',
            self::VIEW_ALL_STUDENTS => 'عرض جميع الطالبات',
            self::VIEW_ASSIGNED_STUDENTS => 'عرض الطالبات المسندات',
            self::IMPORT_STUDENTS => 'استيراد الطالبات من ملف',
            self::MANAGE_SETTINGS => 'إدارة إعدادات المنصة',
            self::MANAGE_CONTENT => 'إدارة المحتوى التعليمي ومصادره وتصنيفاته',
            self::MANAGE_LINKS => 'إدارة الروابط المهمة',
        };
    }
}
