<?php

namespace App\Services;

use App\Enums\PermissionName;
use App\Models\User;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;

/**
 * Changes which permissions each role grants.
 *
 * Only permissions defined in PermissionName can be granted. An admin can
 * never remove, from a role they hold, the permissions needed to reach this
 * screen again, so nobody can lock themselves out by accident.
 */
class RolePermissionService
{
    /**
     * Permissions an editor may not remove from a role they hold.
     */
    public const SELF_PROTECTED = [PermissionName::ACCESS_ADMIN_AREA, PermissionName::MANAGE_ROLES];

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @return Collection<int, Role>
     */
    public function roles(): Collection
    {
        return Role::query()->where('guard_name', 'web')->with('permissions')->withCount('users')->orderBy('id')->get();
    }

    /**
     * @param  list<string>  $permissionNames
     */
    public function sync(Role $role, array $permissionNames, User $editor): void
    {
        $valid = array_map(fn (PermissionName $permission) => $permission->value, PermissionName::cases());
        $unknown = array_diff($permissionNames, $valid);

        if ($unknown !== []) {
            throw new InvalidArgumentException('صلاحية غير معروفة.');
        }

        if ($editor->hasRole($role)) {
            foreach (self::SELF_PROTECTED as $protected) {
                if (! in_array($protected->value, $permissionNames, true)) {
                    throw new InvalidArgumentException("لا يمكنك إزالة صلاحية «{$protected->label()}» من دور تحمله، حتى لا تفقد الوصول إلى هذه الصفحة.");
                }
            }
        }

        $old = $role->permissions->pluck('name')->sort()->values()->all();
        $new = collect($permissionNames)->unique()->sort()->values()->all();

        if ($old === $new) {
            return;
        }

        $role->syncPermissions($new);

        $this->audit->record('role.permissions-updated', $role, ['permissions' => $old], ['permissions' => $new]);
    }
}
