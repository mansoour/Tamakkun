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
| `counselor` | الموجهة الطلابية | `counselor-area.access`, `students.view-assigned` |
| `admin` | مدير النظام | `admin-area.access`, `schools.manage`, `users.manage`, `students.view-all`, `students.import` |
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

Added by migrations `2026_10_03_000002_…` (v0.1) and `2026_10_04_000003_add_school_management_permissions` (v0.2).

Area permissions and feature permissions are independent. A user with only `admin-area.access` can open the admin dashboard, but not the schools or users pages.

## Policies

| Policy | Model | Rules |
|---|---|---|
| `StudentProfilePolicy` | `StudentProfile` | `viewAny`: view-all or view-assigned · `view`: view-all, or view-assigned **and** assigned counselor · `create`/`update`: users.manage |

The sidebar (`App\Support\Navigation::visibleTo`) hides links the user is not permitted to open.

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
