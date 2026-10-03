<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Enums\ContentType;
use App\Enums\QuestionType;
use App\Models\Content;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Quiz engine (brief §54): admins write the questions of a "quiz" content
 * item; students submit answers, get an instant score and complete the
 * content when they reach the pass percentage. Retakes are allowed.
 */
class QuizService
{
    public const MAX_QUESTIONS = 20;

    public function __construct(
        private readonly ContentCompletionService $completion,
        private readonly ActivityLogger $activity,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Replaces the quiz's questions. Earlier attempts keep their scores, but
     * their per-question answers are removed with the old questions.
     *
     * @param  array{pass_percentage: int, questions: list<array{question_type: string, prompt: string, explanation?: ?string, options?: list<?string>, correct: int|string}>}  $data
     */
    public function saveQuestions(Content $content, array $data, ?User $editor): Quiz
    {
        if ($content->content_type !== ContentType::QUIZ) {
            throw new InvalidArgumentException('الأسئلة تضاف فقط لمحتوى من نوع «اختبار قصير».');
        }

        return DB::transaction(function () use ($content, $data, $editor) {
            $quiz = Quiz::firstOrNew(['content_id' => $content->id]);
            $quiz->fill(['pass_percentage' => $data['pass_percentage'], 'updated_by' => $editor?->id])->save();
            $quiz->questions()->delete();

            foreach (array_values($data['questions']) as $i => $q) {
                $question = $quiz->questions()->create([
                    'question_type' => $q['question_type'],
                    'prompt' => $q['prompt'],
                    'explanation' => $q['explanation'] ?? null,
                    'sort_order' => $i,
                ]);

                $labels = $q['question_type'] === QuestionType::TRUE_FALSE->value
                    ? ['صح', 'خطأ']
                    : array_values(array_filter($q['options'] ?? [], fn ($label) => trim((string) $label) !== ''));

                foreach ($labels as $j => $label) {
                    $question->options()->create(['label' => $label, 'is_correct' => $j === (int) $q['correct'], 'sort_order' => $j]);
                }
            }

            $this->audit->record('quiz.updated', $content, null, [
                'questions' => count($data['questions']), 'pass_percentage' => $quiz->pass_percentage,
            ]);

            return $quiz;
        });
    }

    /**
     * Grades a submission. Every question must have exactly one chosen option.
     *
     * @param  array<int|string, int|string>  $choices  question id => option id
     */
    public function submit(User $student, Quiz $quiz, array $choices): QuizAttempt
    {
        $quiz->loadMissing('questions.options', 'content');

        if (! $quiz->content->isVisible() || $quiz->questions->isEmpty()) {
            throw new InvalidArgumentException('هذا الاختبار غير متاح.');
        }

        return DB::transaction(function () use ($student, $quiz, $choices) {
            $graded = $quiz->questions->map(function ($question) use ($choices) {
                $option = $question->options->firstWhere('id', (int) ($choices[$question->id] ?? 0));

                if ($option === null) {
                    throw new InvalidArgumentException('أجيبي عن كل الأسئلة قبل الإرسال.');
                }

                return ['question' => $question, 'option' => $option];
            });

            $correct = $graded->filter(fn (array $g) => $g['option']->is_correct)->count();
            $total = $graded->count();
            $percentage = (int) round($correct / $total * 100);

            $attempt = $quiz->attempts()->create([
                'student_id' => $student->id,
                'correct_count' => $correct,
                'question_count' => $total,
                'percentage' => $percentage,
                'passed' => $percentage >= $quiz->pass_percentage,
                'submitted_at' => now(),
            ]);

            foreach ($graded as $g) {
                $attempt->answers()->create([
                    'quiz_question_id' => $g['question']->id,
                    'question_option_id' => $g['option']->id,
                    'is_correct' => $g['option']->is_correct,
                ]);
            }

            $this->activity->log($student, ActivityEvent::QUIZ_SUBMITTED, $quiz->content, ['percentage' => $percentage]);

            $attempt->passed
                ? $this->completion->complete($student, $quiz->content)
                : $this->completion->start($student, $quiz->content);

            return $attempt;
        });
    }

    public function latestAttempt(User $student, Quiz $quiz): ?QuizAttempt
    {
        return $quiz->attempts()->where('student_id', $student->id)->latest('submitted_at')->latest('id')
            ->with('answers')->first();
    }

    public function bestPercentage(User $student, Quiz $quiz): ?int
    {
        $best = $quiz->attempts()->where('student_id', $student->id)->max('percentage');

        return $best === null ? null : (int) $best;
    }

    /**
     * A student's attempts across all quizzes, newest first (for counselors).
     *
     * @return Collection<int, QuizAttempt>
     */
    public function recentAttempts(User $student, int $limit = 10): Collection
    {
        return QuizAttempt::where('student_id', $student->id)->with('quiz.content')
            ->latest('submitted_at')->latest('id')->limit($limit)->get();
    }
}
