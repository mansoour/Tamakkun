<?php

namespace Tests\Feature\Admin;

use App\Enums\PermissionName;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    private function role(string $name): Role
    {
        return Role::findByName($name, 'web');
    }

    public function test_admin_sees_roles_with_their_permissions(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/roles')
            ->assertOk()
            ->assertSee('الموجهة الطلابية')
            ->assertSee(PermissionName::FOLLOW_UP_STUDENTS->label());
    }

    public function test_users_without_the_permission_cannot_open_or_change_roles(): void
    {
        $counselor = User::factory()->counselor()->create();
        $role = $this->role('counselor');

        $this->actingAs($counselor)->get('/admin/roles')->assertForbidden();
        $this->actingAs($counselor)->put("/admin/roles/{$role->id}", ['permissions' => [PermissionName::MANAGE_SETTINGS->value]])->assertForbidden();
    }

    public function test_admin_grants_a_permission_and_it_takes_effect_and_is_audited(): void
    {
        $admin = User::factory()->admin()->create();
        $counselor = User::factory()->counselor()->create();
        $role = $this->role('counselor');
        $this->assertFalse($counselor->can(PermissionName::MANAGE_CONTENT->value));

        $permissions = [...$role->permissions->pluck('name')->all(), PermissionName::MANAGE_CONTENT->value, PermissionName::ACCESS_ADMIN_AREA->value];

        $this->actingAs($admin)->get("/admin/roles/{$role->id}/edit")->assertOk();
        $this->actingAs($admin)->put("/admin/roles/{$role->id}", ['permissions' => $permissions])
            ->assertRedirect('/admin/roles');

        $this->assertTrue($counselor->fresh()->can(PermissionName::MANAGE_CONTENT->value));
        $this->actingAs($counselor->fresh())->get('/admin/content')->assertOk();

        $log = AuditLog::where('action', 'role.permissions-updated')->sole();
        $this->assertContains(PermissionName::MANAGE_CONTENT->value, $log->new_values['permissions']);
        $this->assertNotContains(PermissionName::MANAGE_CONTENT->value, $log->old_values['permissions']);
    }

    public function test_admin_cannot_lock_themselves_out_of_a_role_they_hold(): void
    {
        $admin = User::factory()->admin()->create();
        $role = $this->role('admin');
        $without = $role->permissions->pluck('name')->reject(fn ($name) => $name === PermissionName::MANAGE_ROLES->value)->values()->all();

        $this->actingAs($admin)->from("/admin/roles/{$role->id}/edit")
            ->put("/admin/roles/{$role->id}", ['permissions' => $without])
            ->assertRedirect("/admin/roles/{$role->id}/edit")
            ->assertSessionHasErrors('permissions');

        $this->assertTrue($admin->fresh()->can(PermissionName::MANAGE_ROLES->value));
        $this->assertSame(0, AuditLog::where('action', 'role.permissions-updated')->count());
    }

    public function test_unknown_permissions_are_rejected(): void
    {
        $role = $this->role('student');

        $this->actingAs(User::factory()->admin()->create())
            ->put("/admin/roles/{$role->id}", ['permissions' => ['everything.allowed']])
            ->assertSessionHasErrors('permissions.0');
    }
}
