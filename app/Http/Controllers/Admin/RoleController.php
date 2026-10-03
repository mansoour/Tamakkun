<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateRolePermissionsRequest;
use App\Services\RolePermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(private readonly RolePermissionService $roles) {}

    public function index(): View
    {
        return view('admin.roles.index', ['roles' => $this->roles->roles()]);
    }

    public function edit(Request $request, Role $role): View
    {
        return view('admin.roles.edit', [
            'role' => $role->load('permissions'),
            'roleLabel' => RoleName::tryFrom($role->name)?->label() ?? $role->name,
            'permissions' => PermissionName::cases(),
            'protected' => $request->user()->hasRole($role) ? RolePermissionService::SELF_PROTECTED : [],
        ]);
    }

    public function update(UpdateRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        try {
            $this->roles->sync($role, $request->permissionNames(), $request->user());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['permissions' => $e->getMessage()])->withInput();
        }

        return to_route('admin.roles.index')->with('success', 'تم حفظ صلاحيات الدور.');
    }
}
