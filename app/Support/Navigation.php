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
                ['label' => 'موعدي ودرجتي', 'icon' => 'calendar-days', 'route' => 'student.exams.index', 'active' => 'student.exams.*'],
                ['label' => 'دفعة اليوم', 'icon' => 'sparkles', 'route' => 'student.motivation'],
                ['label' => 'القدرات الكمي', 'icon' => 'calculator', 'route' => 'student.quantitative'],
                ['label' => 'القدرات اللفظي', 'icon' => 'book-open', 'route' => 'student.verbal'],
                ['label' => 'التحصيلي', 'icon' => 'beaker', 'route' => 'student.achievement', 'active' => 'student.achievement*'],
                ['label' => 'تحدي اليوم', 'icon' => 'bolt', 'route' => 'student.challenge'],
                ['label' => 'مكتبة المقاطع', 'icon' => 'play-circle', 'route' => 'student.videos'],
                ['label' => 'روابط مهمة', 'icon' => 'link', 'route' => 'student.links'],
                ['label' => 'تقدمي', 'icon' => 'chart-bar', 'route' => 'student.progress'],
                ['label' => 'الإشعارات', 'icon' => 'bell', 'route' => 'student.notifications'],
                ['label' => 'المفضلة', 'icon' => 'bookmark', 'route' => 'student.favorites'],
                ['label' => 'حسابي', 'icon' => 'user-circle', 'route' => 'profile.edit'],
            ],
            'counselor' => [
                ['label' => 'لوحة الموجهة الطلابية', 'icon' => 'squares-2x2', 'route' => 'counselor.dashboard'],
                ['label' => 'الطالبات', 'icon' => 'users', 'route' => 'counselor.students.index', 'active' => 'counselor.students.*', 'permission' => PermissionName::VIEW_ASSIGNED_STUDENTS],
                ['label' => 'تحتاج متابعة', 'icon' => 'flag', 'route' => 'counselor.follow-up', 'permission' => PermissionName::VIEW_ASSIGNED_STUDENTS],
                ['label' => 'التنبيهات', 'icon' => 'exclamation-triangle', 'route' => 'counselor.alerts', 'permission' => PermissionName::VIEW_ASSIGNED_STUDENTS],
                ['label' => 'النتائج', 'icon' => 'presentation-chart-line', 'route' => 'counselor.results', 'permission' => PermissionName::VIEW_ASSIGNED_STUDENTS],
                ['label' => 'الاختبارات القادمة', 'icon' => 'calendar-days', 'route' => 'counselor.exams', 'permission' => PermissionName::VIEW_ASSIGNED_STUDENTS],
                ['label' => 'الإعلانات', 'icon' => 'megaphone', 'route' => 'counselor.announcements.index', 'active' => 'counselor.announcements.*', 'permission' => PermissionName::SEND_ANNOUNCEMENTS],
                ['label' => 'التقارير', 'icon' => 'document-text', 'route' => 'counselor.reports.index', 'active' => 'counselor.reports.*', 'permission' => PermissionName::VIEW_REPORTS],
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
                ['label' => 'المحتوى التعليمي', 'icon' => 'book-open', 'route' => 'admin.content.index', 'active' => 'admin.content.*', 'permission' => PermissionName::MANAGE_CONTENT],
                ['label' => 'المصادر', 'icon' => 'building-library', 'route' => 'admin.sources.index', 'active' => 'admin.sources.*', 'permission' => PermissionName::MANAGE_CONTENT],
                ['label' => 'تصنيفات القدرات', 'icon' => 'squares-2x2', 'route' => 'admin.categories.index', 'active' => 'admin.categories.*', 'permission' => PermissionName::MANAGE_CONTENT],
                ['label' => 'مواد التحصيلي', 'icon' => 'beaker', 'route' => 'admin.subjects.index', 'active' => 'admin.subjects.*', 'permission' => PermissionName::MANAGE_CONTENT],
                ['label' => 'أبواب التحصيلي', 'icon' => 'bookmark', 'route' => 'admin.chapters.index', 'active' => 'admin.chapters.*', 'permission' => PermissionName::MANAGE_CONTENT],
                ['label' => 'موضوعات التحصيلي', 'icon' => 'document-text', 'route' => 'admin.topics.index', 'active' => 'admin.topics.*', 'permission' => PermissionName::MANAGE_CONTENT],
                ['label' => 'الروابط المهمة', 'icon' => 'link', 'route' => 'admin.links.index', 'active' => 'admin.links.*', 'permission' => PermissionName::MANAGE_LINKS],
                ['label' => 'تحدي اليوم', 'icon' => 'bolt', 'route' => 'admin.challenges.index', 'active' => 'admin.challenges.*', 'permission' => PermissionName::MANAGE_CHALLENGES],
                ['label' => 'دفعة اليوم', 'icon' => 'sparkles', 'route' => 'admin.motivations.index', 'active' => 'admin.motivations.*', 'permission' => PermissionName::MANAGE_MOTIVATIONS],
                ['label' => 'الإعلانات', 'icon' => 'megaphone', 'route' => 'admin.announcements.index', 'active' => 'admin.announcements.*', 'permission' => PermissionName::ANNOUNCE_TO_ALL],
                ['label' => 'التقارير', 'icon' => 'document-text', 'route' => 'admin.reports.index', 'active' => 'admin.reports.*', 'permission' => PermissionName::VIEW_REPORTS],
                ['label' => 'الأدوار والصلاحيات', 'icon' => 'key', 'route' => 'admin.roles.index', 'active' => 'admin.roles.*', 'permission' => PermissionName::MANAGE_ROLES],
                ['label' => 'الإعدادات', 'icon' => 'cog-6-tooth', 'route' => 'admin.settings.edit', 'active' => 'admin.settings.*', 'permission' => PermissionName::MANAGE_SETTINGS],
                ['label' => 'سجل التدقيق', 'icon' => 'clipboard-document-list', 'route' => 'admin.audit-logs', 'permission' => PermissionName::VIEW_AUDIT_LOGS],
                ['label' => 'سجل البريد', 'icon' => 'envelope', 'route' => 'admin.email-logs', 'permission' => PermissionName::VIEW_EMAIL_LOGS],
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
