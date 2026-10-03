<?php

namespace Tests\Feature;

use App\Enums\PermissionName;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AreaAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_from_every_area(): void
    {
        foreach (['/dashboard', '/student/dashboard', '/counselor/dashboard', '/admin/dashboard'] as $uri) {
            $this->get($uri)->assertRedirect('/login');
        }
    }

    public function test_after_login_each_role_lands_on_its_own_dashboard(): void
    {
        $cases = [
            'student.dashboard' => User::factory()->student()->create(),
            'counselor.dashboard' => User::factory()->counselor()->create(),
            'admin.dashboard' => User::factory()->admin()->create(),
        ];

        foreach ($cases as $route => $user) {
            $this->actingAs($user)->get('/dashboard')->assertRedirect(route($route));
        }
    }

    public function test_student_can_see_own_dashboard(): void
    {
        $student = User::factory()->student()->create(['name' => 'ريم التجريبية']);

        $this->actingAs($student)
            ->get('/student/dashboard')
            ->assertOk()
            ->assertSee('ريم التجريبية')
            ->assertSee('الاختبار القادم');
    }

    public function test_student_cannot_access_counselor_or_admin_areas(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get('/counselor/dashboard')->assertForbidden();
        $this->actingAs($student)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_counselor_can_access_counselor_area_but_not_admin_or_student_areas(): void
    {
        $counselor = User::factory()->counselor()->create();

        $this->actingAs($counselor)->get('/counselor/dashboard')->assertOk();
        $this->actingAs($counselor)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($counselor)->get('/student/dashboard')->assertForbidden();
    }

    public function test_admin_can_access_admin_area(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk()->assertSee('لوحة الإدارة');
    }

    public function test_access_is_driven_by_permissions_not_role_names(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionName::ACCESS_COUNSELOR_AREA->value);

        $this->actingAs($user)->get('/counselor/dashboard')->assertOk();
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('counselor.dashboard'));
    }

    public function test_user_without_any_area_permission_gets_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertForbidden();
    }

    public function test_logged_in_users_visiting_login_are_sent_to_their_dashboard(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get('/login')->assertRedirect(route('dashboard'));
    }
}
