<?php

namespace Tests\Feature\Counselor;

use App\Enums\AlertStatus;
use App\Enums\FollowUpStatus;
use App\Models\AuditLog;
use App\Models\CounselorNote;
use App\Models\CounselorProfile;
use App\Models\ExamAttempt;
use App\Models\StudentAlert;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\StudentAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowUpTest extends TestCase
{
    use RefreshDatabase;

    private User $counselor;

    private StudentProfile $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->counselor = CounselorProfile::factory()->create()->user;
        $this->student = StudentProfile::factory()->assignedTo($this->counselor)->create();
    }

    public function test_dashboard_shows_real_kpis(): void
    {
        $booked = StudentProfile::factory()->assignedTo($this->counselor)->create();
        ExamAttempt::factory()->booked(5)->create(['student_id' => $booked->user_id]);
        ExamAttempt::factory()->withScore(70, 60)->create(['student_id' => $booked->user_id, 'attempt_number' => 2, 'exam_type' => 'tahsili']);
        ExamAttempt::factory()->withScore(80, 10)->create(['student_id' => $booked->user_id, 'attempt_number' => 3, 'exam_type' => 'tahsili']);
        StudentProfile::factory()->create(); // not assigned

        $this->actingAs($this->counselor)->get('/counselor/dashboard')
            ->assertOk()
            ->assertSee('متوسط التحسن')
            ->assertViewHas('kpis', fn ($k) => $k['students'] === 2
                && $k['not_booked'] === 1
                && $k['upcoming_exams'] === 1
                && $k['average_improvement'] === 10.0
                && $k['inactive'] === 0, 'brand-new accounts are not inactive yet');
    }

    public function test_roster_shows_only_assigned_students_and_supports_filters(): void
    {
        $booked = StudentProfile::factory()->assignedTo($this->counselor)->create(['user_id' => User::factory()->student()->create(['name' => 'زهرة-محجوزة'])->id]);
        ExamAttempt::factory()->booked()->create(['student_id' => $booked->user_id]);
        $this->student->user->update(['name' => 'وردة-غير-محجوزة']);
        StudentProfile::factory()->create(['user_id' => User::factory()->student()->create(['name' => 'ليست-لي'])->id]);

        $this->actingAs($this->counselor)->get('/counselor/students')
            ->assertOk()->assertSee('زهرة-محجوزة')->assertSee('وردة-غير-محجوزة')->assertDontSee('ليست-لي');

        $this->actingAs($this->counselor)->get('/counselor/students?booking=not_booked')
            ->assertSee('وردة-غير-محجوزة')->assertDontSee('زهرة-محجوزة');

        $this->actingAs($this->counselor)->get('/counselor/students?q=زهرة')
            ->assertSee('زهرة-محجوزة')->assertDontSee('وردة-غير-محجوزة');
    }

    public function test_student_page_has_all_tabs(): void
    {
        $response = $this->actingAs($this->counselor)->get("/counselor/students/{$this->student->id}")->assertOk();

        foreach (['نظرة عامة', 'الدرجات', 'الاختبارات', 'الأنشطة', 'المحتوى', 'التحديات', 'الملاحظات', 'التنبيهات'] as $tab) {
            $response->assertSee($tab);
        }
    }

    public function test_counselor_can_change_follow_up_status_and_it_is_audited(): void
    {
        $this->actingAs($this->counselor)
            ->patch("/counselor/students/{$this->student->id}/follow-up", ['follow_up_status' => 'needs_followup'])
            ->assertSessionHasNoErrors();

        $this->assertSame(FollowUpStatus::NEEDS_FOLLOWUP, $this->student->fresh()->follow_up_status);
        $log = AuditLog::where('action', 'student.follow_up_changed')->sole();
        $this->assertSame(['follow_up_status' => 'needs_followup'], $log->new_values);

        $this->actingAs($this->counselor)->get('/counselor/follow-up')->assertSee($this->student->user->name);
    }

    public function test_notes_are_private_by_default_and_never_shown_to_the_student(): void
    {
        $this->actingAs($this->counselor)->post("/counselor/students/{$this->student->id}/notes", ['note' => 'ملاحظة-سرية']);
        $this->actingAs($this->counselor)->post("/counselor/students/{$this->student->id}/notes", ['note' => 'رسالة-مشتركة', 'share_with_student' => '1']);

        $this->assertTrue(CounselorNote::where('note', 'ملاحظة-سرية')->sole()->is_private);
        $this->assertFalse(CounselorNote::where('note', 'رسالة-مشتركة')->sole()->is_private);
        $this->assertStringNotContainsString('ملاحظة-سرية', AuditLog::all()->toJson(JSON_UNESCAPED_UNICODE));

        $this->actingAs($this->student->user)->get('/student/dashboard')
            ->assertOk()->assertSee('رسالة-مشتركة')->assertDontSee('ملاحظة-سرية');

        $this->actingAs($this->counselor)->get("/counselor/students/{$this->student->id}")
            ->assertSee('ملاحظة-سرية')->assertSee('رسالة-مشتركة');
    }

    public function test_students_cannot_reach_notes_or_follow_up_routes(): void
    {
        $user = $this->student->user;

        $this->actingAs($user)->post("/counselor/students/{$this->student->id}/notes", ['note' => 'x'])->assertForbidden();
        $this->actingAs($user)->get("/counselor/students/{$this->student->id}")->assertForbidden();
        $this->assertDatabaseCount('counselor_notes', 0);
    }

    public function test_unassigned_counselor_cannot_add_notes_or_change_status(): void
    {
        $other = CounselorProfile::factory()->create()->user;

        $this->actingAs($other)->post("/counselor/students/{$this->student->id}/notes", ['note' => 'x'])->assertForbidden();
        $this->actingAs($other)->patch("/counselor/students/{$this->student->id}/follow-up", ['follow_up_status' => 'watch'])->assertForbidden();
        $this->assertSame(FollowUpStatus::NORMAL, $this->student->fresh()->follow_up_status);
    }

    public function test_only_the_author_can_delete_a_note(): void
    {
        $this->actingAs($this->counselor)->post("/counselor/students/{$this->student->id}/notes", ['note' => 'x']);
        $note = CounselorNote::sole();

        $colleague = CounselorProfile::factory()->create()->user;
        $this->student->update(['counselor_id' => $colleague->id]);
        $this->actingAs($colleague)->delete("/counselor/students/{$this->student->id}/notes/{$note->id}")->assertForbidden();

        $this->student->update(['counselor_id' => $this->counselor->id]);
        $this->actingAs($this->counselor)->delete("/counselor/students/{$this->student->id}/notes/{$note->id}")->assertRedirect();
        $this->assertModelMissing($note);
    }

    public function test_counselor_can_acknowledge_and_resolve_alerts(): void
    {
        app(StudentAlertService::class)->refreshFor($this->student->user);
        $alert = StudentAlert::where('student_id', $this->student->user_id)->first();

        $this->actingAs($this->counselor)->get('/counselor/alerts')->assertOk()->assertSee($alert->title);

        $this->actingAs($this->counselor)->post("/counselor/alerts/{$alert->id}/acknowledge");
        $this->assertSame(AlertStatus::ACKNOWLEDGED, $alert->fresh()->status);

        $this->actingAs($this->counselor)->post("/counselor/alerts/{$alert->id}/resolve");
        $this->assertSame(AlertStatus::RESOLVED, $alert->fresh()->status);
        $this->assertSame($this->counselor->id, $alert->fresh()->resolved_by);
        $this->assertDatabaseHas('audit_logs', ['action' => 'alert.resolved']);
    }

    public function test_unassigned_counselor_cannot_see_or_resolve_alerts(): void
    {
        app(StudentAlertService::class)->refreshFor($this->student->user);
        $alert = StudentAlert::where('student_id', $this->student->user_id)->first();
        $other = CounselorProfile::factory()->create()->user;

        $this->actingAs($other)->get('/counselor/alerts')->assertDontSee($this->student->user->name);
        $this->actingAs($other)->post("/counselor/alerts/{$alert->id}/resolve")->assertForbidden();
        $this->assertNotSame(AlertStatus::RESOLVED, $alert->fresh()->status);
    }

    public function test_exams_and_results_pages(): void
    {
        ExamAttempt::factory()->booked(3)->create(['student_id' => $this->student->user_id]);
        $scored = StudentProfile::factory()->assignedTo($this->counselor)->create();
        ExamAttempt::factory()->withScore(65, 50)->create(['student_id' => $scored->user_id]);
        ExamAttempt::factory()->withScore(74, 5)->create(['student_id' => $scored->user_id, 'attempt_number' => 2]);

        $unbooked = StudentProfile::factory()->assignedTo($this->counselor)->create();

        $this->actingAs($this->counselor)->get('/counselor/exams')->assertOk()->assertSee('بعد 3 أيام')
            ->assertSee('لم يحجزن (1)')->assertSee($unbooked->user->name);
        $this->actingAs($this->counselor)->get('/counselor/results')->assertOk()->assertSee('+9');
    }
}
