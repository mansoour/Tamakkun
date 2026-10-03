<?php

namespace App\Support;

use App\Enums\PermissionName;
use App\Models\User;

/**
 * Sidebar/drawer navigation for each area.
 *
 * Items whose `route` is null are sections planned for a later phase.
 * They are rendered as clearly labelled, non-clickable "قريبًا" entries so
 * the interface never shows a button that does nothing.
 */
class Navigation
{
    /**
     * Items for an area, without the ones the user is not permitted to open.
     *
     * @return list<array{label: string, icon: string, route: string|null, active?: string, permission?: PermissionName}>
     */
    public static function visibleTo(?User $user, string $area): array
    {
        return array_values(array_filter(
            self::for($area),
            fn (array $item) => ! isset($item['permission']) || $user?->can($item['permission']->value),
        ));
    }

    /**
     * @return list<array{label: string, icon: string, route: string|null, active?: string, permission?: PermissionName}>
     */
    public static function for(string $area): array
    {
        return match ($area) {
            'student' => [
                ['label' => 'الرئيسية', 'icon' => 'home', 'route' => 'student.dashboard'],
                ['label' => 'القدرات الكمي', 'icon' => 'calculator', 'route' => null],
                ['label' => 'القدرات اللفظي', 'icon' => 'book-open', 'route' => null],
                ['label' => 'التحصيلي', 'icon' => 'beaker', 'route' => null],
                ['label' => 'تحدي اليوم', 'icon' => 'bolt', 'route' => null],
                ['label' => 'مكتبة المقاطع', 'icon' => 'play-circle', 'route' => null],
                ['label' => 'روابط مهمة', 'icon' => 'link', 'route' => null],
                ['label' => 'موعدي ودرجتي', 'icon' => 'calendar-days', 'route' => null],
                ['label' => 'دفعة اليوم', 'icon' => 'sparkles', 'route' => null],
                ['label' => 'تقدمي', 'icon' => 'chart-bar', 'route' => null],
                ['label' => 'الإشعارات', 'icon' => 'bell', 'route' => null],
                ['label' => 'المفضلة', 'icon' => 'bookmark', 'route' => null],
                ['label' => 'حسابي', 'icon' => 'user-circle', 'route' => 'profile.edit'],
            ],
            'counselor' => [
                ['label' => 'لوحة الموجهة الطلابية', 'icon' => 'squares-2x2', 'route' => 'counselor.dashboard'],
                ['label' => 'الطالبات', 'icon' => 'users', 'route' => 'counselor.students.index', 'active' => 'counselor.students.*', 'permission' => PermissionName::VIEW_ASSIGNED_STUDENTS],
                ['label' => 'تحتاج متابعة', 'icon' => 'flag', 'route' => null],
                ['label' => 'التنبيهات', 'icon' => 'exclamation-triangle', 'route' => null],
                ['label' => 'النتائج', 'icon' => 'presentation-chart-line', 'route' => null],
                ['label' => 'الاختبارات القادمة', 'icon' => 'calendar-days', 'route' => null],
                ['label' => 'المحتوى', 'icon' => 'book-open', 'route' => null],
                ['label' => 'الإعلانات', 'icon' => 'megaphone', 'route' => null],
                ['label' => 'التقارير', 'icon' => 'document-text', 'route' => null],
                ['label' => 'حسابي', 'icon' => 'user-circle', 'route' => 'profile.edit'],
            ],
            'admin' => [
                ['label' => 'لوحة الإدارة', 'icon' => 'squares-2x2', 'route' => 'admin.dashboard'],
                ['label' => 'المدارس', 'icon' => 'building-library', 'route' => 'admin.schools.index', 'active' => 'admin.schools.*', 'permission' => PermissionName::MANAGE_SCHOOLS],
                ['label' => 'الأعوام الدراسية', 'icon' => 'calendar-days', 'route' => 'admin.academic-years.index', 'active' => 'admin.academic-years.*', 'permission' => PermissionName::MANAGE_SCHOOLS],
                ['label' => 'الصفوف', 'icon' => 'academic-cap', 'route' => 'admin.grades.index', 'active' => 'admin.grades.*', 'permission' => PermissionName::MANAGE_SCHOOLS],
                ['label' => 'الفصول', 'icon' => 'squares-2x2', 'route' => 'admin.classes.index', 'active' => 'admin.classes.*', 'permission' => PermissionName::MANAGE_SCHOOLS],
                ['label' => 'الطالبات', 'icon' => 'users', 'route' => 'admin.students.index', 'active' => 'admin.students.*', 'permission' => PermissionName::MANAGE_USERS],
                ['label' => 'الموجهات', 'icon' => 'user-circle', 'route' => 'admin.counselors.index', 'active' => 'admin.counselors.*', 'permission' => PermissionName::MANAGE_USERS],
                ['label' => 'استيراد الطالبات', 'icon' => 'document-text', 'route' => 'admin.imports.create', 'active' => 'admin.imports.*', 'permission' => PermissionName::IMPORT_STUDENTS],
                ['label' => 'المحتوى والمصادر', 'icon' => 'book-open', 'route' => null],
                ['label' => 'الروابط المهمة', 'icon' => 'link', 'route' => null],
                ['label' => 'الإعلانات', 'icon' => 'megaphone', 'route' => null],
                ['label' => 'التقارير', 'icon' => 'document-text', 'route' => null],
                ['label' => 'الإعدادات', 'icon' => 'cog-6-tooth', 'route' => 'admin.settings.edit', 'active' => 'admin.settings.*', 'permission' => PermissionName::MANAGE_SETTINGS],
                ['label' => 'سجل التدقيق', 'icon' => 'clipboard-document-list', 'route' => null],
                ['label' => 'حسابي', 'icon' => 'user-circle', 'route' => 'profile.edit'],
            ],
            default => [],
        };
    }

    /**
     * Arabic title shown in the header for each area.
     */
    public static function title(string $area): string
    {
        return match ($area) {
            'student' => 'مساحة الطالبة',
            'counselor' => 'لوحة الموجهة الطلابية',
            'admin' => 'لوحة الإدارة',
            default => 'تمكّن',
        };
    }
}
