<?php

namespace Tests\Feature\Admin;

use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ViewAsUserTest extends TestCase
{
    use RefreshDatabase;

    private function student(): User
    {
        return StudentProfile::factory()->create()->user;
    }

    public function test_admin_views_the_student_area_as_the_student_with_a_banner(): void
    {
        $admin = User::factory()->admin()->create();
        $student = $this->student();

        $this->actingAs($admin)->post("/admin/users/{$student->id}/view-as")
            ->assertRedirect(route('student.dashboard'));

        $this->get('/student/dashboard')
            ->assertOk()
            ->assertSee('تعرضين المنصة كما يراها')
            ->assertSee($student->name)
            ->assertSee(route('view-as.stop'), false);

        $this->assertSame('user.view-as-started', AuditLog::latest('id')->first()->action);
        $this->assertSame($student->id, AuditLog::latest('id')->first()->auditable_id);
    }

    public function test_writes_are_blocked_while_viewing(): void
    {
        $admin = User::factory()->admin()->create();
        $student = $this->student();

        $this->actingAs($admin)->post("/admin/users/{$student->id}/view-as");

        $this->from('/student/exams')->post('/student/exams', ['exam_type' => 'qudurat', 'booking_status' => 'not_booked'])
            ->assertRedirect('/student/exams')
            ->assertSessionHas('info');

        $this->assertSame(0, $student->examAttempts()->count());
    }

    public function test_pages_that_record_on_open_leave_no_trace(): void
    {
        $admin = User::factory()->admin()->create();
        $student = $this->student();
        $id = (string) Str::uuid();
        $student->notifications()->create(['id' => $id, 'type' => 'test', 'data' => ['title' => 'إشعار', 'url' => url('/student/dashboard')]]);

        $this->actingAs($admin)->post("/admin/users/{$student->id}/view-as");
        $this->get("/student/notifications/{$id}")->assertRedirect();

        $this->assertNull($student->notifications()->sole()->read_at);
    }

    public function test_admin_area_is_not_reachable_as_the_viewed_user_and_stopping_restores_the_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $student = $this->student();

        $this->actingAs($admin)->post("/admin/users/{$student->id}/view-as");
        $this->get('/admin/dashboard')->assertForbidden();

        $this->post('/view-as/stop')->assertRedirect(route('admin.dashboard'));
        $this->get('/admin/dashboard')->assertOk()->assertDontSee('تعرضين المنصة كما يراها');
        $this->assertSame('user.view-as-ended', AuditLog::latest('id')->first()->action);
    }

    public function test_admins_and_disabled_users_cannot_be_viewed(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();
        $disabled = $this->student();
        $disabled->forceFill(['status' => UserStatus::DISABLED])->save();

        $this->actingAs($admin)->post("/admin/users/{$otherAdmin->id}/view-as")->assertSessionHasErrors('view_as');
        $this->actingAs($admin)->post("/admin/users/{$disabled->id}/view-as")->assertSessionHasErrors('view_as');
        $this->get('/admin/dashboard')->assertOk()->assertDontSee('تعرضين المنصة كما يراها');
    }

    public function test_users_without_the_permission_cannot_start_viewing(): void
    {
        $counselor = User::factory()->counselor()->create();

        $this->actingAs($counselor)->post("/admin/users/{$this->student()->id}/view-as")->assertForbidden();
    }

    public function test_view_as_button_appears_in_the_student_list(): void
    {
        $student = $this->student();

        $this->actingAs(User::factory()->admin()->create())->get('/admin/students')
            ->assertSee(route('admin.users.view-as', $student), false);
    }
}
