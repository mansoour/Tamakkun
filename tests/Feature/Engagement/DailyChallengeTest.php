<?php

namespace Tests\Feature\Engagement;

use App\Enums\ActivityEvent;
use App\Models\ActivityLog;
use App\Models\ChallengeAnswer;
use App\Models\CounselorProfile;
use App\Models\DailyChallenge;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\StudentProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyChallengeTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private DailyChallenge $challenge;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->student()->create();
        $this->challenge = DailyChallenge::factory()->create();
    }

    private function question(int $i = 0)
    {
        return $this->challenge->questions[$i];
    }

    public function test_student_sees_todays_challenge_without_the_answer(): void
    {
        $this->actingAs($this->student)->get('/student/challenge')
            ->assertOk()
            ->assertSee('سؤال تجريبي 0')
            ->assertSee('سؤال تجريبي 1')
            ->assertDontSee('شرح تجريبي 0')
            ->assertDontSee('is_correct');
    }

    public function test_correct_answer_is_recorded_with_feedback_and_streak(): void
    {
        $correct = $this->question()->options->firstWhere('is_correct', true);

        $this->actingAs($this->student)->post("/student/challenge/questions/{$this->question()->id}/answer", ['option_id' => $correct->id])
            ->assertRedirect('/student/challenge')
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'إجابة صحيحة') && str_contains($m, 'سلسلة أيامك الآن: 1'));

        $this->assertTrue(ChallengeAnswer::sole()->is_correct);
        $this->assertSame(ActivityEvent::CHALLENGE_ANSWERED, ActivityLog::sole()->event_type);
        $this->assertSame(1, app(StudentProgressService::class)->streak($this->student));

        $this->actingAs($this->student)->get('/student/challenge')->assertSee('شرح تجريبي 0')->assertSee('إجابتك');
    }

    public function test_wrong_answer_is_marked_and_explained(): void
    {
        $wrong = $this->question()->options->firstWhere('is_correct', false);

        $this->actingAs($this->student)->post("/student/challenge/questions/{$this->question()->id}/answer", ['option_id' => $wrong->id])
            ->assertSessionHas('info', fn ($m) => str_contains($m, 'غير صحيحة'));

        $this->assertFalse(ChallengeAnswer::sole()->is_correct);
        $this->actingAs($this->student)->get('/student/challenge')->assertSee('إجابة غير صحيحة')->assertSee('شرح تجريبي 0');
    }

    public function test_each_question_can_only_be_answered_once(): void
    {
        [$first, $second] = $this->question()->options;

        $this->actingAs($this->student)->post("/student/challenge/questions/{$this->question()->id}/answer", ['option_id' => $second->id]);
        $this->actingAs($this->student)->post("/student/challenge/questions/{$this->question()->id}/answer", ['option_id' => $first->id])
            ->assertSessionHasErrors('answer');

        $this->assertFalse(ChallengeAnswer::sole()->is_correct);
    }

    public function test_options_from_another_question_are_rejected(): void
    {
        $foreign = $this->question(1)->options->first();

        $this->actingAs($this->student)->post("/student/challenge/questions/{$this->question()->id}/answer", ['option_id' => $foreign->id])
            ->assertSessionHasErrors('answer');

        $this->assertDatabaseCount('challenge_answers', 0);
    }

    public function test_unpublished_or_past_challenges_cannot_be_answered(): void
    {
        $this->challenge->update(['challenge_date' => today()->subDay()]);
        $option = $this->question()->options->first();

        $this->actingAs($this->student)->post("/student/challenge/questions/{$this->question()->id}/answer", ['option_id' => $option->id])
            ->assertSessionHasErrors('answer');

        $draft = DailyChallenge::factory()->draft()->create();
        $this->actingAs($this->student)->get('/student/challenge')->assertSee('لا يوجد تحدٍّ لهذا اليوم');
        $this->actingAs($this->student)->post("/student/challenge/questions/{$draft->questions[0]->id}/answer", ['option_id' => $draft->questions[0]->options[0]->id])
            ->assertSessionHasErrors('answer');
    }

    public function test_admin_can_create_a_challenge_with_both_question_types(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/challenges/create')->assertOk();
        $this->actingAs($admin)->post('/admin/challenges', [
            'challenge_date' => today()->addDay()->toDateString(), 'is_published' => '1',
            'questions' => [
                ['section' => 'quantitative', 'question_type' => 'multiple_choice', 'prompt' => 'كم 2+2؟', 'explanation' => '4', 'options' => ['3', '4', '', ''], 'correct' => 1],
                ['section' => 'verbal', 'question_type' => 'true_false', 'prompt' => 'السماء زرقاء', 'correct' => 0],
            ],
        ])->assertRedirect('/admin/challenges');

        $challenge = DailyChallenge::latest('id')->first();
        $this->assertSame(['3', '4'], $challenge->questions[0]->options->pluck('label')->all());
        $this->assertSame('4', $challenge->questions[0]->options->firstWhere('is_correct', true)->label);
        $this->assertSame(['صح', 'خطأ'], $challenge->questions[1]->options->pluck('label')->all());
        $this->assertDatabaseHas('audit_logs', ['action' => 'challenge.created']);
    }

    public function test_challenge_validation(): void
    {
        $admin = User::factory()->admin()->create();
        $base = ['challenge_date' => today()->addDays(3)->toDateString()];

        $this->actingAs($admin)->post('/admin/challenges', $base + ['questions' => [
            ['section' => 'quantitative', 'question_type' => 'multiple_choice', 'prompt' => 'x', 'options' => ['فقط', '', '', ''], 'correct' => 0],
        ]])->assertSessionHasErrors('questions.0.options');

        $this->actingAs($admin)->post('/admin/challenges', $base + ['questions' => [
            ['section' => 'quantitative', 'question_type' => 'multiple_choice', 'prompt' => 'x', 'options' => ['أ', 'ب', '', ''], 'correct' => 3],
        ]])->assertSessionHasErrors('questions.0.correct');

        $this->actingAs($admin)->post('/admin/challenges', ['challenge_date' => today()->toDateString(), 'questions' => [
            ['section' => 'verbal', 'question_type' => 'true_false', 'prompt' => 'x', 'correct' => 0],
        ]])->assertSessionHasErrors('challenge_date');
    }

    public function test_answered_challenges_cannot_be_rewritten(): void
    {
        $admin = User::factory()->admin()->create();
        ChallengeAnswer::create([
            'student_id' => $this->student->id, 'challenge_question_id' => $this->question()->id,
            'challenge_option_id' => $this->question()->options[0]->id, 'is_correct' => true, 'answered_at' => now(),
        ]);

        $this->actingAs($admin)->put("/admin/challenges/{$this->challenge->id}", [
            'challenge_date' => today()->toDateString(),
            'questions' => [['section' => 'verbal', 'question_type' => 'true_false', 'prompt' => 'تغيير', 'correct' => 0]],
        ])->assertSessionHasErrors('delete');

        $this->assertSame('سؤال تجريبي 0', $this->challenge->fresh()->questions[0]->prompt);
    }

    public function test_only_permitted_users_manage_challenges(): void
    {
        $this->actingAs(User::factory()->counselor()->create())->get('/admin/challenges')->assertForbidden();
        $this->actingAs($this->student)->get('/admin/challenges')->assertForbidden();
    }

    public function test_counselor_sees_challenge_answers_tab(): void
    {
        $counselor = CounselorProfile::factory()->create()->user;
        $profile = StudentProfile::factory()->assignedTo($counselor)->create(['user_id' => $this->student->id]);
        $this->actingAs($this->student)->post("/student/challenge/questions/{$this->question()->id}/answer", ['option_id' => $this->question()->options[0]->id]);

        $this->actingAs($counselor)->get("/counselor/students/{$profile->id}")
            ->assertSee('إجابات صحيحة: 1 من 1');
    }
}
