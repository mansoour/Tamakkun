<?php

namespace Tests\Feature\Student;

use App\Enums\ActivityEvent;
use App\Enums\ExamBookingStatus;
use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\CounselorProfile;
use App\Models\ExamAttempt;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\ExamProgressService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamTrackingTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->student()->create();
    }

    private function summary(?User $student = null): array
    {
        return app(ExamProgressService::class)->summary($student ?? $this->student);
    }

    public function test_student_can_add_a_booked_exam_and_see_the_countdown(): void
    {
        $this->actingAs($this->student)->get('/student/exams/create')->assertOk();

        $this->actingAs($this->student)->post('/student/exams', [
            'exam_type' => 'qudurat', 'booking_status' => 'booked', 'exam_date' => now()->addDays(18)->toDateString(),
        ])->assertRedirect('/student/exams');

        $attempt = ExamAttempt::sole();
        $this->assertSame(1, $attempt->attempt_number);
        $this->assertSame($this->student->id, $attempt->student_id);

        $this->actingAs($this->student)->get('/student/exams')->assertSee('القدرات — بعد 18 يومًا');
        $this->actingAs($this->student)->get('/student/dashboard')->assertSee('بعد 18 يومًا');
    }

    public function test_attempt_numbers_increase_per_exam_type(): void
    {
        foreach (['qudurat', 'qudurat', 'tahsili'] as $type) {
            $this->actingAs($this->student)->post('/student/exams', ['exam_type' => $type, 'booking_status' => 'not_booked']);
        }

        $this->assertSame([1, 2], ExamAttempt::where('exam_type', 'qudurat')->orderBy('id')->pluck('attempt_number')->all());
        $this->assertSame([1], ExamAttempt::where('exam_type', 'tahsili')->pluck('attempt_number')->all());
    }

    public function test_student_can_enter_multiple_scores_and_see_latest_best_and_improvement(): void
    {
        ExamAttempt::factory()->withScore(70, 120)->create(['student_id' => $this->student->id, 'attempt_number' => 1]);
        ExamAttempt::factory()->withScore(79, 60)->create(['student_id' => $this->student->id, 'attempt_number' => 2]);
        ExamAttempt::factory()->withScore(76, 10)->create(['student_id' => $this->student->id, 'attempt_number' => 3, 'target_score' => 85]);

        $qudurat = $this->summary()['qudurat'];
        $this->assertSame(76, $qudurat['latest']);
        $this->assertSame(79, $qudurat['best']);
        $this->assertSame(-3, $qudurat['improvement']);
        $this->assertSame(85, $qudurat['target']);
        $this->assertSame(6, $qudurat['gap']);

        $this->actingAs($this->student)->get('/student/exams')
            ->assertSee('انخفضت بمقدار 3 درجات عن المحاولة السابقة');
    }

    public function test_target_falls_back_to_the_admin_default(): void
    {
        app(SettingsService::class)->set('default_target_score', 90);
        ExamAttempt::factory()->withScore(80)->create(['student_id' => $this->student->id]);

        $qudurat = $this->summary()['qudurat'];
        $this->assertSame(90, $qudurat['target']);
        $this->assertTrue($qudurat['target_is_default']);
        $this->assertSame(10, $qudurat['gap']);
        $this->assertNull($qudurat['improvement']);
    }

    public function test_next_exam_is_the_soonest_upcoming_booked_attempt(): void
    {
        ExamAttempt::factory()->booked(30)->create(['student_id' => $this->student->id]);
        ExamAttempt::factory()->booked(5)->create(['student_id' => $this->student->id, 'exam_type' => 'tahsili']);
        ExamAttempt::factory()->booked(-3)->create(['student_id' => $this->student->id, 'attempt_number' => 2]);

        $next = app(ExamProgressService::class)->nextExam($this->summary());
        $this->assertSame('tahsili', $next['attempt']->exam_type->value);
        $this->assertSame(5, $next['days']);
        $this->assertSame(30, $this->summary()['qudurat']['days_until_next']);
    }

    public function test_validation_rules_for_dates_and_scores(): void
    {
        $post = fn (array $data) => $this->actingAs($this->student)->post('/student/exams', $data + ['exam_type' => 'qudurat']);

        $post(['booking_status' => 'booked'])->assertSessionHasErrors('exam_date');
        $post(['booking_status' => 'result_received', 'exam_date' => now()->subDay()->toDateString()])->assertSessionHasErrors('score');
        $post(['booking_status' => 'result_received', 'exam_date' => now()->subDay()->toDateString(), 'score' => 101])->assertSessionHasErrors('score');
        $post(['booking_status' => 'completed', 'exam_date' => now()->addWeek()->toDateString()])->assertSessionHasErrors('exam_date');
        $post(['booking_status' => 'not_booked', 'exam_type' => 'other'])->assertSessionHasErrors('exam_type');

        $this->assertDatabaseCount('exam_attempts', 0);
    }

    public function test_score_is_dropped_unless_the_result_was_received(): void
    {
        $this->actingAs($this->student)->post('/student/exams', [
            'exam_type' => 'qudurat', 'booking_status' => 'booked', 'exam_date' => now()->addWeek()->toDateString(), 'score' => 80,
        ]);

        $this->assertNull(ExamAttempt::sole()->score);
    }

    public function test_student_can_update_own_exam_and_changes_are_audited(): void
    {
        $attempt = ExamAttempt::factory()->booked(10)->create(['student_id' => $this->student->id]);

        $this->actingAs($this->student)->put("/student/exams/{$attempt->id}", [
            'booking_status' => 'result_received', 'exam_date' => now()->subDay()->toDateString(), 'score' => 82,
        ])->assertRedirect('/student/exams');

        $this->assertSame(ExamBookingStatus::RESULT_RECEIVED, $attempt->fresh()->booking_status);
        $this->assertSame(82, $attempt->fresh()->score);

        $log = AuditLog::where('action', 'exam.updated')->sole();
        $this->assertSame(['booking_status' => 'booked', 'exam_date' => now()->addDays(10)->toDateString(), 'score' => null], $log->old_values);
        $this->assertSame(82, $log->new_values['score']);
        $this->assertSame(ActivityEvent::EXAM_UPDATED, ActivityLog::sole()->event_type);
    }

    public function test_exam_type_cannot_be_changed_on_update(): void
    {
        $attempt = ExamAttempt::factory()->create(['student_id' => $this->student->id]);

        $this->actingAs($this->student)->put("/student/exams/{$attempt->id}", ['exam_type' => 'tahsili', 'booking_status' => 'not_booked'])
            ->assertSessionHasErrors('exam_type');
    }

    public function test_student_cannot_view_edit_or_delete_another_students_exam(): void
    {
        $attempt = ExamAttempt::factory()->booked()->create();

        $this->actingAs($this->student)->get("/student/exams/{$attempt->id}/edit")->assertForbidden();
        $this->actingAs($this->student)->put("/student/exams/{$attempt->id}", ['booking_status' => 'not_booked'])->assertForbidden();
        $this->actingAs($this->student)->delete("/student/exams/{$attempt->id}")->assertForbidden();
        $this->actingAs($this->student)->get('/student/exams')->assertDontSee($attempt->exam_date->format('Y-m-d'));

        $this->assertModelExists($attempt);
    }

    public function test_student_can_delete_own_attempt(): void
    {
        $attempt = ExamAttempt::factory()->create(['student_id' => $this->student->id]);

        $this->actingAs($this->student)->delete("/student/exams/{$attempt->id}")->assertRedirect('/student/exams');

        $this->assertModelMissing($attempt);
        $this->assertDatabaseHas('audit_logs', ['action' => 'exam.deleted']);
    }

    public function test_assigned_counselor_sees_exams_but_unassigned_cannot(): void
    {
        $counselor = CounselorProfile::factory()->create()->user;
        $profile = StudentProfile::factory()->assignedTo($counselor)->create(['user_id' => $this->student->id]);
        ExamAttempt::factory()->withScore(77)->create(['student_id' => $this->student->id]);

        $this->actingAs($counselor)->get("/counselor/students/{$profile->id}")
            ->assertOk()->assertSee('الاختبارات والدرجات')->assertSee('77');

        $other = CounselorProfile::factory()->create()->user;
        $this->actingAs($other)->get("/counselor/students/{$profile->id}")->assertForbidden();
    }

    public function test_counselors_cannot_use_student_exam_routes(): void
    {
        $counselor = User::factory()->counselor()->create();

        $this->actingAs($counselor)->post('/student/exams', ['exam_type' => 'qudurat', 'booking_status' => 'not_booked'])->assertForbidden();
    }
}
