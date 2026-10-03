<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Enums\ContentSection;
use App\Enums\ProgressStatus;
use App\Models\ActivityLog;
use App\Models\Content;
use App\Models\StudentContentProgress;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * The single source of truth for student progress numbers. Every page
 * that shows a percentage must get it from here (docs/progress.md).
 *
 * Formulas:
 * - completion % = completed visible items ÷ visible items (overall and per section)
 * - weekly = items completed since the start of the week (Sunday, app timezone)
 *   against the `weekly_content_goal` setting
 * - streak = consecutive days, ending today or yesterday, with at least one
 *   learning action (start or complete)
 */
class StudentProgressService
{
    public function __construct(private readonly SettingsService $settings) {}

    /**
     * @return array{
     *     completion: array{completed: int, total: int, percentage: int},
     *     sections: array<string, array{label: string, completed: int, total: int, percentage: int}>,
     *     weekly: array{completed: int, goal: int, percentage: int},
     *     streak: int,
     *     in_progress: int,
     *     last_activity_at: ?CarbonImmutable
     * }
     */
    public function summary(User $student): array
    {
        $totals = Content::visible()->selectRaw('section, count(*) as total')->groupBy('section')->pluck('total', 'section');
        $completed = $this->completedVisible($student)->selectRaw('contents.section, count(*) as total')
            ->groupBy('contents.section')->pluck('total', 'section');

        $sections = [];
        foreach (ContentSection::cases() as $section) {
            $sections[$section->value] = [
                'label' => $section->label(),
                ...$this->ratio((int) ($completed[$section->value] ?? 0), (int) ($totals[$section->value] ?? 0)),
            ];
        }

        return [
            'completion' => $this->ratio((int) $completed->sum(), (int) $totals->sum()),
            'sections' => $sections,
            'weekly' => $this->weekly($student),
            'streak' => $this->streak($student),
            'in_progress' => StudentContentProgress::where('student_id', $student->id)->where('status', ProgressStatus::IN_PROGRESS)->count(),
            'last_activity_at' => $this->lastActivityAt($student),
        ];
    }

    /**
     * @return array{completed: int, goal: int, percentage: int}
     */
    public function weekly(User $student): array
    {
        $goal = max(1, (int) $this->settings->get('weekly_content_goal'));
        $completed = $this->completedVisible($student)
            ->where('student_content_progress.completed_at', '>=', $this->startOfWeek())
            ->count();

        return ['completed' => $completed, 'goal' => $goal, 'percentage' => min(100, (int) floor($completed * 100 / $goal))];
    }

    public function streak(User $student): int
    {
        $today = CarbonImmutable::now()->startOfDay();

        $days = ActivityLog::where('user_id', $student->id)
            ->whereIn('event_type', ActivityEvent::learningValues())
            ->where('created_at', '>=', $today->subDays(365))
            ->pluck('created_at')
            ->map(fn ($at) => CarbonImmutable::parse($at)->toDateString())
            ->unique()
            ->flip();

        $day = $days->has($today->toDateString()) ? $today : $today->subDay();
        $streak = 0;

        while ($days->has($day->toDateString())) {
            $streak++;
            $day = $day->subDay();
        }

        return $streak;
    }

    public function lastActivityAt(User $student): ?CarbonImmutable
    {
        $at = ActivityLog::where('user_id', $student->id)->whereIn('event_type', ActivityEvent::learningValues())->max('created_at');

        return $at ? CarbonImmutable::parse($at) : null;
    }

    /**
     * Progress status per content id, for badges on lists.
     *
     * @param  iterable<int>  $contentIds
     * @return Collection<int, ProgressStatus>
     */
    public function statuses(User $student, iterable $contentIds): Collection
    {
        return StudentContentProgress::where('student_id', $student->id)
            ->whereIn('content_id', collect($contentIds)->all())
            ->pluck('status', 'content_id');
    }

    public function startOfWeek(): CarbonImmutable
    {
        return CarbonImmutable::now()->startOfWeek(CarbonImmutable::SUNDAY);
    }

    /**
     * @return Builder<StudentContentProgress>
     */
    private function completedVisible(User $student)
    {
        return StudentContentProgress::query()
            ->join('contents', 'contents.id', '=', 'student_content_progress.content_id')
            ->where('student_content_progress.student_id', $student->id)
            ->where('student_content_progress.status', ProgressStatus::COMPLETED)
            ->where('contents.is_published', true)
            ->whereNull('contents.archived_at')
            ->where(fn ($q) => $q->whereNull('contents.published_at')->orWhere('contents.published_at', '<=', now()));
    }

    /**
     * @return array{completed: int, total: int, percentage: int}
     */
    private function ratio(int $completed, int $total): array
    {
        return [
            'completed' => $completed,
            'total' => $total,
            'percentage' => $total > 0 ? (int) floor($completed * 100 / $total) : 0,
        ];
    }
}
