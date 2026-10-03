<?php

namespace Tests\Feature\Admin;

use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\Classroom;
use App\Models\CounselorProfile;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Classroom $classroom;

    private User $counselor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->classroom = Classroom::factory()->create();
        $this->counselor = CounselorProfile::factory()->forSchool($this->classroom->grade->academicYear->school)->create()->user;
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'نورة التجريبية',
            'student_code' => 'S1001',
            'username' => '',
            'password' => 'secret-pass',
            'school_id' => $this->classroom->schoolId(),
            'classroom_id' => $this->classroom->id,
            'counselor_id' => $this->counselor->id,
            'status' => 'active',
        ], $overrides);
    }

    public function test_admin_can_create_a_student_who_can_then_log_in(): void
    {
        $this->actingAs($this->admin)->post('/admin/students', $this->payload())->assertRedirect('/admin/students');

        $student = StudentProfile::with('user')->sole();
        $this->assertSame('S1001', $student->student_code);
        $this->assertSame('S1001', $student->user->username, 'username defaults to the student code');
        $this->assertNull($student->user->email);
        $this->assertSame(UserStatus::ACTIVE, $student->user->status);
        $this->assertTrue($student->user->hasRole('student'));
        $this->assertSame($this->counselor->id, $student->counselor_id);

        $this->assertDatabaseHas('audit_logs', ['action' => 'user.created']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'student.created']);

        $this->post('/logout');
        $this->post('/login', ['login' => 'S1001', 'password' => 'secret-pass']);
        $this->assertAuthenticatedAs($student->user);
    }

    public function test_pending_student_cannot_log_in_until_activated(): void
    {
        $this->actingAs($this->admin)->post('/admin/students', $this->payload(['status' => 'pending']));
        $user = User::where('username', 'S1001')->sole();
        $this->post('/logout');

        $this->post('/login', ['login' => 'S1001', 'password' => 'secret-pass'])->assertSessionHasErrors('login');
        $this->assertGuest();

        $this->actingAs($this->admin)->patch("/admin/users/{$user->id}/status", ['status' => 'active'])->assertSessionHasNoErrors();
        $this->assertSame(UserStatus::ACTIVE, $user->fresh()->status);

        $log = AuditLog::where('action', 'user.status_changed')->latest('id')->first();
        $this->assertSame(['status' => 'pending'], $log->old_values);
        $this->assertSame(['status' => 'active'], $log->new_values);
    }

    public function test_classroom_and_counselor_must_belong_to_the_selected_school(): void
    {
        $otherClassroom = Classroom::factory()->create();
        $otherCounselor = CounselorProfile::factory()->create()->user;

        $this->actingAs($this->admin)
            ->post('/admin/students', $this->payload(['classroom_id' => $otherClassroom->id, 'counselor_id' => $otherCounselor->id]))
            ->assertSessionHasErrors(['classroom_id', 'counselor_id']);

        $this->assertDatabaseCount('student_profiles', 0);
    }

    public function test_a_non_counselor_cannot_be_assigned_as_counselor(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/students', $this->payload(['counselor_id' => $this->admin->id]))
            ->assertSessionHasErrors('counselor_id');
    }

    public function test_student_code_and_username_must_be_unique_and_latin(): void
    {
        StudentProfile::factory()->create(['student_code' => 'S1001']);

        $this->actingAs($this->admin)->post('/admin/students', $this->payload())->assertSessionHasErrors('student_code');
        $this->actingAs($this->admin)->post('/admin/students', $this->payload(['student_code' => 'رقم']))->assertSessionHasErrors('student_code');
    }

    public function test_reassigning_a_counselor_is_audited_and_passwords_are_never_logged(): void
    {
        $student = StudentProfile::factory()->inClassroom($this->classroom)->create();
        $student->load('user');

        $this->actingAs($this->admin)->put("/admin/students/{$student->id}", [
            'name' => $student->user->name,
            'student_code' => $student->student_code,
            'username' => $student->user->username,
            'password' => 'brand-new-pass',
            'school_id' => $student->school_id,
            'classroom_id' => $this->classroom->id,
            'counselor_id' => $this->counselor->id,
        ])->assertRedirect('/admin/students');

        $this->assertSame($this->counselor->id, $student->fresh()->counselor_id);

        $assigned = AuditLog::where('action', 'student.assigned_to_counselor')->sole();
        $this->assertSame(['counselor_id' => $this->counselor->id], $assigned->new_values);

        $this->assertStringNotContainsString('brand-new-pass', AuditLog::all()->toJson());
    }

    public function test_admin_cannot_disable_their_own_account(): void
    {
        $this->actingAs($this->admin)
            ->patch("/admin/users/{$this->admin->id}/status", ['status' => 'disabled'])
            ->assertSessionHasErrors('account_status');

        $this->assertSame(UserStatus::ACTIVE, $this->admin->fresh()->status);
    }

    public function test_student_list_can_be_searched_and_filtered(): void
    {
        StudentProfile::factory()->create(['user_id' => User::factory()->student()->create(['name' => 'زمردة-بحث'])->id]);
        StudentProfile::factory()->assignedTo($this->counselor)->create(['user_id' => User::factory()->student()->create(['name' => 'ياقوتة-مسندة'])->id]);

        $this->actingAs($this->admin)->get('/admin/students?q=زمردة')->assertSee('زمردة-بحث')->assertDontSee('ياقوتة-مسندة');
        $this->actingAs($this->admin)->get('/admin/students?unassigned=1')->assertSee('زمردة-بحث')->assertDontSee('ياقوتة-مسندة');
    }

    public function test_counselors_cannot_manage_students(): void
    {
        $this->actingAs($this->counselor)->get('/admin/students')->assertForbidden();
        $this->actingAs($this->counselor)->post('/admin/students', $this->payload())->assertForbidden();
    }

    public function test_admin_can_create_and_update_a_counselor(): void
    {
        $schoolId = $this->classroom->schoolId();

        $this->actingAs($this->admin)->post('/admin/counselors', [
            'name' => 'أ. سارة', 'username' => 'sara', 'email' => 'sara@example.com',
            'password' => 'secret-pass', 'school_id' => $schoolId, 'status' => 'active',
        ])->assertRedirect('/admin/counselors');

        $profile = CounselorProfile::whereHas('user', fn ($q) => $q->where('username', 'sara'))->sole();
        $this->assertTrue($profile->user->hasRole('counselor'));

        $this->actingAs($this->admin)->put("/admin/counselors/{$profile->id}", [
            'name' => 'أ. سارة', 'username' => 'sara', 'email' => 'sara@example.com',
            'school_id' => $schoolId, 'job_title' => 'موجهة',
        ])->assertRedirect('/admin/counselors');

        $this->assertSame('موجهة', $profile->fresh()->job_title);
        $this->assertDatabaseHas('audit_logs', ['action' => 'counselor.updated']);
    }

    public function test_admin_dashboard_shows_real_counts(): void
    {
        StudentProfile::factory()->count(3)->create();

        $this->actingAs($this->admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('طالبات بدون موجهة')
            ->assertViewHas('metrics', fn ($m) => $m['students'] === 3 && $m['counselors'] === 1 && $m['unassigned'] === 3);
    }
}
