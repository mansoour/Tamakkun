<?php

namespace Tests\Feature\Reports;

use App\Http\Controllers\Shared\ReportController;
use App\Models\ChallengeAnswer;
use App\Models\Content;
use App\Models\CounselorProfile;
use App\Models\DailyChallenge;
use App\Models\ExamAttempt;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\ContentCompletionService;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $counselor;

    private StudentProfile $mine;

    private StudentProfile $other;

    protected function setUp(): void
    {
        parent::setUp();

        $this->counselor = CounselorProfile::factory()->create()->user;
        $this->mine = StudentProfile::factory()->assignedTo($this->counselor)
            ->create(['user_id' => User::factory()->student()->create(['name' => 'طالبتي-المسندة'])->id]);
        $this->other = StudentProfile::factory()
            ->create(['user_id' => User::factory()->student()->create(['name' => 'طالبة-غير-مسندة'])->id]);
    }

    public function test_every_report_renders_for_counselors_and_admins(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($this->counselor)->get('/counselor/reports')->assertOk()->assertSee('تقدّم الفصول');

        foreach (array_keys(ReportService::REPORTS) as $key) {
            $this->actingAs($this->counselor)->get("/counselor/reports/{$key}")->assertOk();
            $this->actingAs($admin)->get("/admin/reports/{$key}")->assertOk();
        }
    }

    public function test_counselor_reports_include_only_assigned_students(): void
    {
        $this->actingAs($this->counselor)->get('/counselor/reports/student-progress')
            ->assertSee('طالبتي-المسندة')->assertDontSee('طالبة-غير-مسندة');

        $this->actingAs(User::factory()->admin()->create())->get('/admin/reports/student-progress')
            ->assertSee('طالبتي-المسندة')->assertSee('طالبة-غير-مسندة');
    }

    public function test_csv_export_is_excel_friendly_utf8_and_audited(): void
    {
        ExamAttempt::factory()->withScore(70, 60)->create(['student_id' => $this->mine->user_id]);
        ExamAttempt::factory()->withScore(81, 5)->create(['student_id' => $this->mine->user_id, 'attempt_number' => 2]);

        $response = $this->actingAs($this->counselor)->get('/counselor/reports/qudurat-scores?format=csv');

        $response->assertOk()->assertDownload();
        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('الطالبة,الفصل,"آخر درجة"', $csv);
        $this->assertStringContainsString('طالبتي-المسندة', $csv);
        $this->assertStringContainsString('+11', $csv);
        $this->assertDatabaseHas('audit_logs', ['action' => 'report.exported']);
    }

    public function test_pdf_export_uses_the_bundled_arabic_font(): void
    {
        $response = $this->actingAs($this->counselor)->get('/counselor/reports/student-progress?format=pdf');

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString('IBMPlexSansArabic', $response->getContent());
    }

    public function test_content_completion_and_challenge_participation_are_counted(): void
    {
        $content = Content::factory()->create(['title' => 'درس-مُنجز']);
        app(ContentCompletionService::class)->complete($this->mine->user, $content);

        $challenge = DailyChallenge::factory()->create();
        $question = $challenge->questions->first();
        ChallengeAnswer::create([
            'student_id' => $this->mine->user_id, 'challenge_question_id' => $question->id,
            'challenge_option_id' => $question->options->first()->id, 'is_correct' => true, 'answered_at' => now(),
        ]);

        $completion = app(ReportService::class)->build('content-completion', $this->counselor);
        $this->assertSame(['درس-مُنجز', 'القدرات الكمي', 'تأسيس', 1, 100], $completion['rows'][0]);

        $participation = app(ReportService::class)->build('challenge-participation', $this->counselor);
        $this->assertSame([1, 1, 100], array_slice($participation['rows'][0], 2));
    }

    public function test_class_progress_groups_by_classroom(): void
    {
        $report = app(ReportService::class)->build('class-progress', User::factory()->admin()->create());

        $this->assertSame('بدون فصل', $report['rows'][0][0]);
        $this->assertSame(2, $report['rows'][0][1]);
    }

    public function test_students_cannot_open_reports_and_unknown_reports_404(): void
    {
        $this->actingAs($this->mine->user)->get('/counselor/reports')->assertForbidden();
        $this->actingAs($this->counselor)->get('/counselor/reports/not-a-report')->assertNotFound();
    }

    public function test_csv_neutralises_formula_injection_but_keeps_signed_numbers(): void
    {
        $this->mine->user->update(['name' => '=HYPERLINK("http://evil.example","x")']);

        $csv = $this->actingAs($this->counselor)->get('/counselor/reports/student-progress?format=csv')->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertSame('+11', ReportController::csvSafe('+11'));
        $this->assertSame("'-cmd", ReportController::csvSafe('-cmd'));
    }
}
