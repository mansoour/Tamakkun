<?php

namespace Tests\Feature\Counselor;

use App\Enums\AlertStatus;
use App\Enums\AlertType;
use App\Models\ActivityLog;
use App\Models\Classroom;
use App\Models\Content;
use App\Models\ExamAttempt;
use App\Models\Grade;
use App\Models\StudentAlert;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\ContentCompletionService;
use App\Services\SettingsService;
use App\Services\StudentAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentAlertsTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->student()->create(['created_at' => now()->subMonth()]);
    }

    private function refresh(): void
    {
        app(StudentAlertService::class)->refreshFor($this->student);
    }

    /**
     * @return list<string>
     */
    private function openTypes(): array
    {
        return StudentAlert::where('student_id', $this->student->id)->unresolved()->pluck('alert_type')->map->value->sort()->values()->all();
    }

    private function studyToday(): void
    {
        app(ContentCompletionService::class)->start($this->student, Content::factory()->create());
    }

    public function test_new_student_without_bookings_or_activity_gets_not_booked_and_inactive_alerts(): void
    {
        $this->refresh();

        $this->assertSame(['inactive', 'not_booked', 'not_booked'], $this->openTypes());
    }

    public function test_refreshing_twice_does_not_duplicate_alerts(): void
    {
        $this->refresh();
        $this->refresh();

        $this->assertSame(3, StudentAlert::count());
    }

    public function test_alerts_auto_resolve_when_the_condition_clears(): void
    {
        $this->refresh();

        $this->studyToday();
        ExamAttempt::factory()->booked(60)->create(['student_id' => $this->student->id]);
        ExamAttempt::factory()->booked(60)->create(['student_id' => $this->student->id, 'exam_type' => 'tahsili']);
        $this->refresh();

        $this->assertSame([], $this->openTypes());
        $this->assertNull(StudentAlert::where('alert_type', AlertType::INACTIVE)->sole()->resolved_by);
    }

    public function test_an_alert_resolved_by_a_counselor_is_not_recreated_while_the_condition_persists(): void
    {
        $this->studyToday();
        $this->refresh();
        $counselor = User::factory()->counselor()->create();

        StudentAlert::where('student_id', $this->student->id)->get()
            ->each(fn ($a) => app(StudentAlertService::class)->resolve($a, $counselor));
        $this->refresh();

        $this->assertSame([], $this->openTypes());
        $this->assertSame(2, StudentAlert::count());
    }

    public function test_not_booked_only_applies_to_grade_12(): void
    {
        $grade = Grade::factory()->create(['level' => 11]);
        StudentProfile::factory()->inClassroom(Classroom::factory()->create(['grade_id' => $grade->id]))->create(['user_id' => $this->student->id]);
        $this->studyToday();

        $this->refresh();

        $this->assertSame([], $this->openTypes());
    }

    public function test_upcoming_exam_with_low_activity_is_critical(): void
    {
        app(SettingsService::class)->setMany(['upcoming_exam_alert_days' => 14, 'low_activity_threshold' => 3]);
        ExamAttempt::factory()->booked(5)->create(['student_id' => $this->student->id]);
        ExamAttempt::factory()->booked(90)->create(['student_id' => $this->student->id, 'exam_type' => 'tahsili']);
        $this->studyToday(); // 1 action < threshold 3

        $this->refresh();

        $alert = StudentAlert::where('alert_type', AlertType::UPCOMING_LOW_ACTIVITY)->sole();
        $this->assertSame('critical', $alert->severity->value);
        $this->assertStringContainsString('بعد 5 أيام', $alert->message);
    }

    public function test_below_target_with_approaching_exam(): void
    {
        ExamAttempt::factory()->withScore(70)->create(['student_id' => $this->student->id, 'target_score' => 85]);
        ExamAttempt::factory()->booked(7)->create(['student_id' => $this->student->id, 'attempt_number' => 2]);
        ExamAttempt::factory()->booked(90)->create(['student_id' => $this->student->id, 'exam_type' => 'tahsili']);

        $this->refresh();

        $this->assertStringContainsString('أفضل درجة في القدرات 70 والهدف 85', StudentAlert::where('alert_type', AlertType::BELOW_TARGET)->sole()->message);
    }

    public function test_improvement_creates_a_positive_alert_once_per_result(): void
    {
        ExamAttempt::factory()->withScore(70, 60)->create(['student_id' => $this->student->id]);
        ExamAttempt::factory()->withScore(78, 10)->create(['student_id' => $this->student->id, 'attempt_number' => 2]);

        $this->refresh();
        $this->refresh();

        $alert = StudentAlert::where('alert_type', AlertType::IMPROVEMENT)->sole();
        $this->assertSame('positive', $alert->severity->value);
        $this->assertStringContainsString('8 درجات', $alert->message);
    }

    public function test_inactivity_threshold_comes_from_settings(): void
    {
        ActivityLog::create(['user_id' => $this->student->id, 'event_type' => 'content_started', 'created_at' => now()->subDays(5)]);

        app(SettingsService::class)->set('inactivity_days', 7);
        $this->refresh();
        $this->assertNotContains('inactive', $this->openTypes());

        app(SettingsService::class)->set('inactivity_days', 3);
        $this->refresh();
        $this->assertContains('inactive', $this->openTypes());
    }

    public function test_exam_changes_refresh_alerts_immediately(): void
    {
        $this->refresh();
        $this->assertContains('not_booked', $this->openTypes());

        $this->actingAs($this->student)->post('/student/exams', [
            'exam_type' => 'qudurat', 'booking_status' => 'booked', 'exam_date' => now()->addMonths(2)->toDateString(),
        ]);

        $this->assertSame(1, StudentAlert::where('alert_type', AlertType::NOT_BOOKED)->where('status', '!=', AlertStatus::RESOLVED)->count());
    }

    public function test_daily_command_refreshes_all_students(): void
    {
        User::factory()->student()->count(2)->create(['created_at' => now()->subMonth()]);

        $this->artisan('tamakkun:refresh-alerts')->assertSuccessful();

        $this->assertSame(3, StudentAlert::where('alert_type', AlertType::INACTIVE)->count());
    }
}
