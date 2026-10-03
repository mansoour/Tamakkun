<?php

namespace App\Services;

use App\Enums\ExamBookingStatus;
use App\Enums\ExamType;
use App\Models\ExamAttempt;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * The single source of exam figures (brief §51). See docs/exams.md.
 *
 * - latest score: score of the most recent attempt with a received result
 *   (by exam date, then attempt number)
 * - best score: highest received score
 * - improvement: latest − previous received score (null with fewer than two)
 * - target: the most recent target the student set for that exam type,
 *   otherwise the `default_target_score` setting
 * - gap: target − best (never negative)
 * - next exam: the earliest booked attempt dated today or later
 */
class ExamProgressService
{
    public function __construct(private readonly SettingsService $settings) {}

    /**
     * @return array<string, array{
     *     type: ExamType,
     *     attempts: Collection<int, ExamAttempt>,
     *     latest: ?int, latest_attempt: ?ExamAttempt, best: ?int, previous: ?int, improvement: ?int,
     *     target: int, target_is_default: bool, gap: ?int,
     *     next: ?ExamAttempt, days_until_next: ?int, booked: bool
     * }>
     */
    public function summary(User $student): array
    {
        return $this->fromAttempts(ExamAttempt::where('student_id', $student->id)->get());
    }

    /**
     * Summaries for many students with a single query (counselor roster, alerts).
     *
     * @param  list<int>  $studentIds
     * @return array<int, array<string, array<string, mixed>>> student id => summary()
     */
    public function summariesFor(array $studentIds): array
    {
        $byStudent = ExamAttempt::whereIn('student_id', $studentIds)->get()->groupBy('student_id');

        $summaries = [];
        foreach ($studentIds as $id) {
            $summaries[$id] = $this->fromAttempts($byStudent->get($id, collect()));
        }

        return $summaries;
    }

    /**
     * @param  Collection<int, ExamAttempt>  $attempts  one student's attempts
     * @return array<string, array<string, mixed>>
     */
    private function fromAttempts(Collection $attempts): array
    {
        $byType = $attempts
            ->sortBy(fn (ExamAttempt $a) => [$a->exam_date?->toDateString() ?? '', $a->attempt_number])
            ->groupBy(fn (ExamAttempt $a) => $a->exam_type->value);

        $summary = [];
        foreach (ExamType::cases() as $type) {
            $summary[$type->value] = $this->forType($type, $byType->get($type->value, collect())->values());
        }

        return $summary;
    }

    /**
     * The soonest upcoming booked exam of any type.
     *
     * @param  array<string, array<string, mixed>>  $summary
     * @return array{attempt: ExamAttempt, days: int}|null
     */
    public function nextExam(array $summary): ?array
    {
        return collect($summary)
            ->filter(fn ($s) => $s['next'] !== null)
            ->sortBy('days_until_next')
            ->map(fn ($s) => ['attempt' => $s['next'], 'days' => $s['days_until_next']])
            ->first();
    }

    /**
     * @param  Collection<int, ExamAttempt>  $attempts  sorted by exam date then attempt number
     * @return array<string, mixed>
     */
    private function forType(ExamType $type, Collection $attempts): array
    {
        $scored = $attempts
            ->filter(fn (ExamAttempt $a) => $a->booking_status === ExamBookingStatus::RESULT_RECEIVED && $a->score !== null)
            ->values();
        $scores = $scored->pluck('score');

        $latest = $scores->last();
        $previous = $scores->count() > 1 ? $scores[$scores->count() - 2] : null;
        $best = $scores->max();

        $explicitTarget = $attempts->sortByDesc('updated_at')->firstWhere('target_score', '!==', null)?->target_score;
        $target = $explicitTarget ?? (int) $this->settings->get('default_target_score');

        $today = CarbonImmutable::today();
        $next = $attempts
            ->filter(fn (ExamAttempt $a) => $a->booking_status === ExamBookingStatus::BOOKED && $a->exam_date && $a->exam_date->gte($today))
            ->sortBy('exam_date')->first();

        return [
            'type' => $type,
            'attempts' => $attempts->sortByDesc('attempt_number')->values(),
            'latest' => $latest,
            'latest_attempt' => $scored->last(),
            'best' => $best,
            'previous' => $previous,
            'improvement' => $previous !== null ? $latest - $previous : null,
            'target' => $target,
            'target_is_default' => $explicitTarget === null,
            'gap' => $best !== null ? max(0, $target - $best) : null,
            'next' => $next,
            'days_until_next' => $next ? (int) $today->diffInDays($next->exam_date->toImmutable()->startOfDay()) : null,
            'booked' => $attempts->contains(fn (ExamAttempt $a) => $a->booking_status !== ExamBookingStatus::NOT_BOOKED),
        ];
    }
}
