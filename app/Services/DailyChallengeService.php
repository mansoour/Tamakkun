<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Enums\QuestionType;
use App\Models\ChallengeAnswer;
use App\Models\ChallengeQuestion;
use App\Models\DailyChallenge;
use App\Models\User;
use App\Notifications\DailyChallengeAvailable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;

/**
 * تحدي اليوم: authoring, answering and the morning notification.
 * A question can be answered once; answers count as learning activity
 * (streak). See docs/engagement.md.
 */
class DailyChallengeService
{
    public function __construct(
        private readonly ActivityLogger $activity,
        private readonly AuditLogger $audit,
        private readonly StudentProgressService $progress,
    ) {}

    public function today(): ?DailyChallenge
    {
        return DailyChallenge::forToday()->with('questions.options')->first();
    }

    /**
     * Answers already given by the student, keyed by question id.
     *
     * @param  iterable<int>  $questionIds
     * @return Collection<int, ChallengeAnswer>
     */
    public function answersFor(User $student, iterable $questionIds): Collection
    {
        return ChallengeAnswer::where('student_id', $student->id)
            ->whereIn('challenge_question_id', collect($questionIds)->all())
            ->get()->keyBy('challenge_question_id');
    }

    /**
     * @return array{answer: ChallengeAnswer, streak: int, already: bool}
     */
    public function answer(User $student, ChallengeQuestion $question, int $optionId): array
    {
        $challenge = $question->challenge;

        if (! $challenge->is_published || ! $challenge->challenge_date->isToday()) {
            throw new InvalidArgumentException('هذا التحدي غير متاح الآن.');
        }

        $option = $question->options()->whereKey($optionId)->first()
            ?? throw new InvalidArgumentException('الخيار غير صالح.');

        $existing = ChallengeAnswer::where('student_id', $student->id)->where('challenge_question_id', $question->id)->first();

        if ($existing) {
            return ['answer' => $existing, 'streak' => $this->progress->streak($student), 'already' => true];
        }

        $answer = DB::transaction(function () use ($student, $question, $option) {
            $answer = ChallengeAnswer::create([
                'student_id' => $student->id,
                'challenge_question_id' => $question->id,
                'challenge_option_id' => $option->id,
                'is_correct' => $option->is_correct,
                'answered_at' => now(),
            ]);
            $this->activity->log($student, ActivityEvent::CHALLENGE_ANSWERED, $question, ['correct' => $option->is_correct]);

            return $answer;
        });

        return ['answer' => $answer, 'streak' => $this->progress->streak($student), 'already' => false];
    }

    /**
     * Creates or replaces a challenge with its questions and options.
     *
     * @param  array{challenge_date: string, title?: ?string, is_published?: bool, questions: list<array<string, mixed>>}  $data
     */
    public function save(?DailyChallenge $challenge, array $data, ?User $author): DailyChallenge
    {
        return DB::transaction(function () use ($challenge, $data, $author) {
            $isNew = $challenge === null;
            $challenge ??= new DailyChallenge(['created_by' => $author?->id]);

            if (! $isNew && ChallengeAnswer::whereIn('challenge_question_id', $challenge->questions()->pluck('id'))->exists()) {
                throw new InvalidArgumentException('لا يمكن تعديل أسئلة تحدٍّ أجابت عنه طالبات. يمكنك إلغاء نشره فقط.');
            }

            $challenge->fill(Arr::only($data, ['challenge_date', 'title']) + ['is_published' => (bool) ($data['is_published'] ?? false)])->save();
            $challenge->questions()->delete();

            foreach (array_values($data['questions']) as $i => $q) {
                $question = $challenge->questions()->create([
                    'section' => $q['section'], 'question_type' => $q['question_type'],
                    'prompt' => $q['prompt'], 'explanation' => $q['explanation'] ?? null, 'sort_order' => $i,
                ]);

                $labels = $q['question_type'] === QuestionType::TRUE_FALSE->value
                    ? ['صح', 'خطأ']
                    : array_values(array_filter($q['options'], fn ($l) => trim((string) $l) !== ''));

                foreach ($labels as $j => $label) {
                    $question->options()->create(['label' => $label, 'is_correct' => $j === (int) $q['correct'], 'sort_order' => $j]);
                }
            }

            $this->audit->record($isNew ? 'challenge.created' : 'challenge.updated', $challenge, null, [
                'date' => $challenge->challenge_date->toDateString(), 'published' => $challenge->is_published,
            ]);

            return $challenge;
        });
    }

    public function setPublished(DailyChallenge $challenge, bool $published): void
    {
        $challenge->update(['is_published' => $published]);
        $this->audit->record($published ? 'challenge.published' : 'challenge.unpublished', $challenge);
    }

    /**
     * Morning notification for today's challenge (once per challenge).
     *
     * @param  iterable<User>  $students
     */
    public function notifyToday(iterable $students): bool
    {
        $challenge = DailyChallenge::forToday()->whereNull('notified_at')->first();

        if ($challenge === null) {
            return false;
        }

        Notification::send($students, new DailyChallengeAvailable);
        $challenge->update(['notified_at' => now()]);

        return true;
    }
}
