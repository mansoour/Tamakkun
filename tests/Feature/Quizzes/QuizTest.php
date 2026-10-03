<?php

namespace Tests\Feature\Quizzes;

use App\Enums\ActivityEvent;
use App\Enums\ContentType;
use App\Enums\ProgressStatus;
use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\Content;
use App\Models\CounselorProfile;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentContentProgress;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\QuizService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizTest extends TestCase
{
    use RefreshDatabase;

    private Content $content;

    protected function setUp(): void
    {
        parent::setUp();

        $this->content = Content::factory()->create(['content_type' => ContentType::QUIZ, 'title' => 'اختبار النسب القصير']);
    }

    /**
     * Two questions: "2 + 2" (correct option index 1) and a true/false whose answer is "خطأ".
     */
    private function quizPayload(int $pass = 60): array
    {
        return [
            'pass_percentage' => $pass,
            'questions' => [
                ['question_type' => 'multiple_choice', 'prompt' => 'كم يساوي 2 + 2؟', 'explanation' => 'جمع بسيط.', 'options' => ['3', '4', '5', ''], 'correct' => 1],
                ['question_type' => 'true_false', 'prompt' => 'العدد 9 عدد أولي.', 'explanation' => '9 = 3 × 3', 'correct' => 1],
            ],
        ];
    }

    private function makeQuiz(int $pass = 60): Quiz
    {
        return app(QuizService::class)->saveQuestions($this->content, $this->quizPayload($pass), null)->load('questions.options');
    }

    /**
     * @return array<int, int> question id => option id
     */
    private function answers(Quiz $quiz, bool $firstCorrect, bool $secondCorrect): array
    {
        [$q1, $q2] = $quiz->questions->all();

        return [
            $q1->id => $q1->options->first(fn ($o) => $o->is_correct === $firstCorrect)->id,
            $q2->id => $q2->options->first(fn ($o) => $o->is_correct === $secondCorrect)->id,
        ];
    }

    public function test_admin_writes_quiz_questions_and_the_change_is_audited(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get("/admin/content/{$this->content->id}/quiz")->assertOk()->assertSee('أسئلة الاختبار');
        $this->actingAs($admin)->put("/admin/content/{$this->content->id}/quiz", $this->quizPayload(80))
            ->assertRedirect("/admin/content/{$this->content->id}/quiz")
            ->assertSessionHasNoErrors();

        $quiz = $this->content->quiz()->with('questions.options')->first();
        $this->assertSame(80, $quiz->pass_percentage);
        $this->assertCount(2, $quiz->questions);
        $this->assertSame(['3', '4', '5'], $quiz->questions[0]->options->pluck('label')->all());
        $this->assertSame('4', $quiz->questions[0]->options->firstWhere('is_correct', true)->label);
        $this->assertSame('خطأ', $quiz->questions[1]->options->firstWhere('is_correct', true)->label);
        $this->assertSame('quiz.updated', AuditLog::latest('id')->first()->action);
    }

    public function test_question_validation_and_non_quiz_content(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put("/admin/content/{$this->content->id}/quiz", [
            'pass_percentage' => 60,
            'questions' => [['question_type' => 'multiple_choice', 'prompt' => 'سؤال', 'options' => ['أ', '', '', ''], 'correct' => 0]],
        ])->assertSessionHasErrors('questions.0.options');

        $lesson = Content::factory()->create();
        $this->actingAs($admin)->get("/admin/content/{$lesson->id}/quiz")->assertNotFound();
        $this->actingAs(User::factory()->counselor()->create())->get("/admin/content/{$this->content->id}/quiz")->assertForbidden();
    }

    public function test_student_sees_questions_without_the_answers(): void
    {
        $this->makeQuiz();

        $this->actingAs(User::factory()->student()->create())
            ->get("/student/content/{$this->content->slug}")
            ->assertOk()
            ->assertSee('كم يساوي 2 + 2؟')
            ->assertSee('إرسال الإجابات')
            ->assertDontSee('جمع بسيط.')
            ->assertDontSee('أنجزت');
    }

    public function test_passing_the_quiz_completes_the_content_and_counts_as_learning(): void
    {
        $quiz = $this->makeQuiz();
        $student = User::factory()->student()->create();

        $this->actingAs($student)->post("/student/content/{$this->content->slug}/quiz", ['answers' => $this->answers($quiz, true, true)])
            ->assertRedirect(route('student.content.show', $this->content).'#quiz-result')
            ->assertSessionHas('success');

        $attempt = QuizAttempt::sole();
        $this->assertSame([2, 2, 100, true], [$attempt->correct_count, $attempt->question_count, $attempt->percentage, $attempt->passed]);
        $this->assertSame(ProgressStatus::COMPLETED, StudentContentProgress::where('student_id', $student->id)->sole()->status);
        $this->assertTrue(ActivityLog::where('event_type', ActivityEvent::QUIZ_SUBMITTED)->exists());

        $this->actingAs($student)->get("/student/content/{$this->content->slug}")
            ->assertSee('نتيجة آخر محاولة')
            ->assertSee('جمع بسيط.');
    }

    public function test_failing_keeps_the_content_in_progress_and_retakes_are_allowed(): void
    {
        $quiz = $this->makeQuiz(60);
        $student = User::factory()->student()->create();

        $this->actingAs($student)->post("/student/content/{$this->content->slug}/quiz", ['answers' => $this->answers($quiz, true, false)])
            ->assertSessionHas('info');

        $this->assertSame(50, QuizAttempt::sole()->percentage);
        $this->assertFalse(QuizAttempt::sole()->passed);
        $this->assertSame(ProgressStatus::IN_PROGRESS, StudentContentProgress::where('student_id', $student->id)->sole()->status);

        $this->actingAs($student)->get("/student/content/{$this->content->slug}")
            ->assertSee('الإجابة الصحيحة: خطأ')
            ->assertSee('أعيدي المحاولة');

        $this->actingAs($student)->post("/student/content/{$this->content->slug}/quiz", ['answers' => $this->answers($quiz, true, true)]);
        $this->assertSame(2, QuizAttempt::count());
        $this->assertSame(ProgressStatus::COMPLETED, StudentContentProgress::where('student_id', $student->id)->sole()->status);
    }

    public function test_every_question_must_be_answered_with_one_of_its_own_options(): void
    {
        $quiz = $this->makeQuiz();
        $otherQuiz = app(QuizService::class)->saveQuestions(Content::factory()->create(['content_type' => ContentType::QUIZ]), $this->quizPayload(), null);
        $student = User::factory()->student()->create();
        [$q1, $q2] = $quiz->questions->all();

        $this->actingAs($student)->post("/student/content/{$this->content->slug}/quiz", ['answers' => [$q1->id => $q1->options[0]->id]])
            ->assertSessionHasErrors('answers');

        $foreign = $otherQuiz->questions[1]->options[0]->id;
        $this->actingAs($student)->post("/student/content/{$this->content->slug}/quiz", ['answers' => [$q1->id => $q1->options[0]->id, $q2->id => $foreign]])
            ->assertSessionHasErrors('answers');

        $this->assertSame(0, QuizAttempt::count());
    }

    public function test_manual_complete_is_refused_for_a_quiz_and_hidden_quizzes_cannot_be_submitted(): void
    {
        $quiz = $this->makeQuiz();
        $student = User::factory()->student()->create();

        $this->actingAs($student)->post("/student/content/{$this->content->slug}/complete")->assertSessionHas('info');
        $this->assertSame(0, StudentContentProgress::where('status', ProgressStatus::COMPLETED)->count());

        $this->content->update(['is_published' => false]);
        $this->actingAs($student)->post("/student/content/{$this->content->slug}/quiz", ['answers' => $this->answers($quiz, true, true)])
            ->assertNotFound();
    }

    public function test_counselor_sees_the_students_quiz_results(): void
    {
        $quiz = $this->makeQuiz();
        $counselor = CounselorProfile::factory()->create()->user;
        $profile = StudentProfile::factory()->assignedTo($counselor)->create();
        app(QuizService::class)->submit($profile->user, $quiz, $this->answers($quiz, true, false));

        $this->actingAs($counselor)->get("/counselor/students/{$profile->id}")
            ->assertOk()
            ->assertSee('اختبار النسب القصير')
            ->assertSee('50%');
    }
}
