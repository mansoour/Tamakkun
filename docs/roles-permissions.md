# Roles and permissions

Tamakkun uses `spatie/laravel-permission`.

## Rules

1. **Code checks permissions, never role names.** Use `can:` route middleware, Policies, `$user->can(...)` and `@can`, always with `App\Enums\PermissionName`.
2. **Roles only group permissions.** `App\Enums\RoleName` exists for seeding and assigning roles, not for authorization.
3. **New permissions are added by migrations** that use the idempotent `Permission::findOrCreate()` and `givePermissionTo()`. Never add them by re-running a seeder, and never wipe or recreate role assignments in production.
4. Record-level rules, such as "a counselor only sees assigned students", will live in Policies as each feature arrives.

## Roles

| Role | Arabic | Permissions |
|---|---|---|
| `student` | الطالبة | `student-area.access` |
| `counselor` | الموجهة الطلابية | `counselor-area.access`, `students.view-assigned`, `students.follow-up`, `announcements.send`, `reports.view` |
| `admin` | مدير النظام | `admin-area.access`, `schools.manage`, `users.manage`, `students.view-all`, `students.import`, `settings.manage`, `content.manage`, `links.manage`, `challenges.manage`, `motivations.manage`, `announcements.manage-all`, `reports.view`, `audit-logs.view`, `email-logs.view`, `roles.manage`, `users.view-as` |
| `guardian` | ولي الأمر | *Not in MVP* |

## Permissions

| Permission | Arabic | Guards |
|---|---|---|
| `student-area.access` | الدخول إلى مساحة الطالبة | `/student/*` |
| `counselor-area.access` | الدخول إلى لوحة الموجهة الطلابية | `/counselor/*` |
| `admin-area.access` | الدخول إلى لوحة الإدارة | `/admin/*` |
| `schools.manage` | إدارة المدارس والأعوام الدراسية والصفوف والفصول | `/admin/schools`, `/admin/academic-years`, `/admin/grades`, `/admin/classes` |
| `users.manage` | إدارة حسابات الطالبات والموجهات | `/admin/students`, `/admin/counselors`, `PATCH /admin/users/{user}/status`; Policy `create`/`update` on students |
| `students.view-all` | عرض جميع الطالبات | Policy `view` on any student |
| `students.view-assigned` | عرض الطالبات المسندات | `/counselor/students`; Policy `view` only when `counselor_id` is the user |
| `students.import` | استيراد الطالبات من ملف | `/admin/imports/students/*` |
| `settings.manage` | إدارة إعدادات المنصة | `/admin/settings` |
| `content.manage` | إدارة المحتوى التعليمي ومصادره وتصنيفاته | `/admin/content`, `/admin/sources`, `/admin/categories`, `/admin/subjects`, `/admin/chapters`, `/admin/topics` |
| `links.manage` | إدارة الروابط المهمة | `/admin/links` |
| `challenges.manage` | إدارة تحدي اليوم | `/admin/challenges` |
| `motivations.manage` | إدارة دفعة اليوم | `/admin/motivations` |
| `announcements.manage-all` | إرسال إعلانات لجميع الطالبات | `/admin/announcements` (all, any class, any student) |
| `announcements.send` | إرسال إعلانات للطالبات المسندات | `/counselor/announcements` (own students only) |
| `reports.view` | عرض التقارير وتصديرها | `/counselor/reports` (own students) and `/admin/reports` (all, via `students.view-all`) |
| `audit-logs.view` | عرض سجل التدقيق | `/admin/audit-logs` |
| `email-logs.view` | عرض سجل البريد | `/admin/email-logs` |
| `roles.manage` | إدارة الأدوار والصلاحيات | `/admin/roles` (v0.9) |
| `users.view-as` | عرض المنصة كما تراها طالبة أو موجهة (قراءة فقط) | `POST /admin/users/{user}/view-as` (v0.9, see [security.md](security.md#view-as-user-v09)) |
| `students.follow-up` | متابعة الطالبات (الملاحظات والتنبيهات وحالة المتابعة) | Notes, follow-up status, alert actions. Combined with viewing the student (`StudentProfilePolicy::followUp`) |

Added by migrations `2026_10_03_000002_…` (v0.1) and `2026_10_04_000003_add_school_management_permissions` (v0.2).

## Changing a role's permissions (v0.9)

Admins with `roles.manage` edit each role's permissions at `/admin/roles` (`App\Services\RolePermissionService`).

- Only permissions in `PermissionName` can be granted. Roles themselves are fixed (student, counselor, admin).
- An editor cannot remove `admin-area.access` or `roles.manage` from a role they hold. The boxes are locked in the form and the service refuses it.
- Every change is audited as `role.permissions-updated` with the old and new lists, and takes effect immediately.
- Example: to let counselors manage content, give the counselor role `content.manage` and `admin-area.access`. They then see only the content sections of the admin area.

Seeded grants still come from migrations. A later migration never removes a permission an admin granted by hand.

Area permissions and feature permissions are independent. A user with only `admin-area.access` can open the admin dashboard, but not the schools or users pages.

## Policies

| Policy | Model | Rules |
|---|---|---|
| `CounselorNotePolicy` | `CounselorNote` | `delete`: author only |
| `StudentAlertPolicy` | `StudentAlert` | `update` (acknowledge or resolve): `followUp` on the student |
| `ExamAttemptPolicy` | `ExamAttempt` | `view`: owner, or anyone who may view the student · `update`/`delete`: owner only |
| `StudentProfilePolicy` | `StudentProfile` | `viewAny`: view-all or view-assigned · `view`: view-all, or view-assigned **and** assigned counselor · `create`/`update`: users.manage |

The sidebar (`App\Support\Navigation::visibleTo`) hides links the user is not permitted to open.

Counselors do not get `content.manage` by default. An admin can grant it to the counselor role or to individual counselors through a migration, or later through the roles UI.

Admins do **not** automatically get the student or counselor areas. Grant those explicitly if needed.

## Post-login redirect

`App\Services\DashboardRedirector` sends the user to the first area they may access, in this order: admin, counselor, student.

## Adding a permission (template)

```php
// database/migrations/YYYY_MM_DD_HHMMSS_add_<feature>_permissions.php
public function up(): void
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $permission = Permission::findOrCreate('content.manage', 'web');
    Role::findOrCreate('admin', 'web')->givePermissionTo($permission);

    app(PermissionRegistrar::class)->forgetCachedPermissions();
}
```

Then add the case to `App\Enums\PermissionName` (with an Arabic `label()`), add a test, and update this file.
