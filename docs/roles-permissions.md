# Roles and permissions

Tamakkun uses `spatie/laravel-permission`.

## Rules

1. **Code checks permissions, never role names.** Use `can:` route middleware, Policies, `$user->can(...)` and `@can`, always with `App\Enums\PermissionName`.
2. **Roles only group permissions.** `App\Enums\RoleName` exists for seeding and assigning roles, not for authorization.
3. **New permissions are added by migrations** that use the idempotent `Permission::findOrCreate()` and `givePermissionTo()`. Never add them by re-running a seeder, and never wipe or recreate role assignments in production.
4. Record-level rules, such as "a counselor only sees assigned students", will live in Policies as each feature arrives.

## Roles (v0.1)

| Role | Arabic | Permissions |
|---|---|---|
| `student` | الطالبة | `student-area.access` |
| `counselor` | الموجهة الطلابية | `counselor-area.access` |
| `admin` | مدير النظام | `admin-area.access` |
| `guardian` | ولي الأمر | *Not in MVP* |

## Permissions (v0.1)

| Permission | Arabic | Guards |
|---|---|---|
| `student-area.access` | الدخول إلى مساحة الطالبة | `/student/*` |
| `counselor-area.access` | الدخول إلى لوحة الموجهة الطلابية | `/counselor/*` |
| `admin-area.access` | الدخول إلى لوحة الإدارة | `/admin/*` |

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
