<?php

namespace Tests\Feature\Auth;

use App\Models\AuditLog;
use App\Models\Classroom;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForcedPasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    private function studentWithAdminSetPassword(): User
    {
        $user = User::factory()->student()->create();
        $user->forceFill(['must_change_password' => true])->save();

        return $user;
    }

    public function test_user_with_admin_set_password_must_change_it_before_using_the_site(): void
    {
        $user = $this->studentWithAdminSetPassword();

        $this->actingAs($user)->get('/student/dashboard')->assertRedirect('/password/change');
        $this->actingAs($user)->get('/profile')->assertRedirect('/password/change');
        $this->actingAs($user)->get('/password/change')->assertOk()->assertSee('تغيير كلمة المرور');
    }

    public function test_changing_the_password_clears_the_requirement(): void
    {
        $user = $this->studentWithAdminSetPassword();

        $this->actingAs($user)->put('/password', [
            'current_password' => 'password',
            'password' => 'my-own-secret',
            'password_confirmation' => 'my-own-secret',
        ])->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('my-own-secret', $user->password));
        $this->actingAs($user)->get('/student/dashboard')->assertOk();
    }

    public function test_new_password_must_differ_from_the_current_one(): void
    {
        $user = $this->studentWithAdminSetPassword();

        $this->actingAs($user)->put('/password', [
            'current_password' => 'password',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrorsIn('updatePassword', 'password');

        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_requirement_is_not_enforced_when_the_admin_turns_the_setting_off(): void
    {
        app(SettingsService::class)->set('force_password_change', false);
        $user = $this->studentWithAdminSetPassword();

        $this->actingAs($user)->get('/student/dashboard')->assertOk();
    }

    public function test_user_without_the_flag_is_not_redirected(): void
    {
        $user = User::factory()->student()->create();

        $this->actingAs($user)->get('/student/dashboard')->assertOk();
        $this->actingAs($user)->get('/password/change')->assertRedirect(route('dashboard'));
    }

    public function test_accounts_created_or_reset_by_an_admin_get_the_flag(): void
    {
        $admin = User::factory()->admin()->create();
        $classroom = Classroom::factory()->create();

        $this->actingAs($admin)->post('/admin/students', [
            'name' => 'طالبة', 'student_code' => 'S77', 'password' => 'initial-pass',
            'school_id' => $classroom->schoolId(), 'status' => 'active',
        ]);
        $this->assertTrue(User::where('username', 'S77')->sole()->must_change_password);

        $student = StudentProfile::factory()->create();
        $this->assertFalse($student->user->must_change_password);

        $this->actingAs($admin)->put("/admin/students/{$student->id}", [
            'name' => $student->user->name, 'student_code' => $student->student_code,
            'username' => $student->user->username, 'school_id' => $student->school_id,
            'password' => 'reset-by-admin',
        ]);
        $this->assertTrue($student->user->fresh()->must_change_password);
    }

    public function test_admin_can_toggle_the_setting_and_the_change_is_audited(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/settings')->assertOk()->assertSee('إلزام تغيير كلمة المرور');

        $this->actingAs($admin)->put('/admin/settings', ['force_password_change' => '0'])
            ->assertRedirect('/admin/settings');

        $this->assertFalse(app(SettingsService::class)->get('force_password_change'));
        $log = AuditLog::where('action', 'settings.updated')->sole();
        $this->assertSame(['force_password_change' => false], $log->new_values);
    }

    public function test_only_users_with_settings_permission_can_change_settings(): void
    {
        $counselor = User::factory()->counselor()->create();

        $this->actingAs($counselor)->get('/admin/settings')->assertForbidden();
        $this->actingAs($counselor)->put('/admin/settings', ['force_password_change' => '0'])->assertForbidden();
        $this->assertTrue(app(SettingsService::class)->get('force_password_change'));
    }
}
