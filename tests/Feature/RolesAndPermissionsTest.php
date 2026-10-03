<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Enums\RoleName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrations_create_every_role_and_permission(): void
    {
        foreach (RoleName::cases() as $role) {
            $this->assertTrue(Role::where('name', $role->value)->exists(), "Missing role {$role->value}");
        }

        foreach (PermissionName::cases() as $permission) {
            $this->assertTrue(Permission::where('name', $permission->value)->exists(), "Missing permission {$permission->value}");
        }
    }

    public function test_roles_receive_their_area_permission(): void
    {
        $this->assertTrue(Role::findByName(RoleName::STUDENT->value)->hasPermissionTo(PermissionName::ACCESS_STUDENT_AREA->value));
        $this->assertTrue(Role::findByName(RoleName::COUNSELOR->value)->hasPermissionTo(PermissionName::ACCESS_COUNSELOR_AREA->value));
        $this->assertTrue(Role::findByName(RoleName::ADMIN->value)->hasPermissionTo(PermissionName::ACCESS_ADMIN_AREA->value));
    }

    public function test_enum_labels_are_arabic(): void
    {
        foreach ([...RoleName::cases(), ...PermissionName::cases()] as $case) {
            $this->assertMatchesRegularExpression('/\p{Arabic}/u', $case->label());
        }
    }
}
