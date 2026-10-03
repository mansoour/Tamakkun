<?php

namespace App\Services;

use App\Enums\AlertSeverity;
use App\Enums\FollowUpStatus;
use App\Enums\PermissionName;
use App\Models\StudentAlert;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Builds the counselor's student roster (brief §57) with every column
 * computed in a few grouped queries — never one query per student.
 * All numbers come from StudentProgressService and ExamProgressService.
 */
class StudentRosterService
{
    public function __construct(
        private readonly StudentProgressService $progress,
        private readonly ExamProgressService $exams,
        private readonly SettingsService $settings,
    ) {}

    /**
     * Students the user may follow: assigned ones, or all with students.view-all.
     *
     * @return Builder<StudentProfile>
     */
    public function scopeFor(User $user): Builder
    {
        return StudentProfile::query()
            ->unless($user->can(PermissionName::VIEW_ALL_STUDENTS->value), fn ($q) => $q->where('counselor_id', $user->id));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(User $user): Collection
    {
        $profiles = $this->scopeFor($user)->with(['user', 'classroom.grade', 'school'])->get();
        $ids = $profiles->pluck('user_id')->all();

        if ($ids === []) {
            return collect();
        }

        $completion = $this->progress->completionFor($ids);
        $lastActivity = $this->progress->lastActivityFor($ids);
        $exams = $this->exams->summariesFor($ids);
        $alerts = StudentAlert::unresolved()->whereIn('student_id', $ids)
            ->whereIn('severity', AlertSeverity::concerningValues())
            ->selectRaw('student_id, count(*) as total')->groupBy('student_id')->pluck('total', 'student_id');

        return $profiles->map(function (StudentProfile $profile) use ($completion, $lastActivity, $exams, $alerts) {
            $id = $profile->user_id;
            $exam = $exams[$id];
            $next = $this->exams->nextExam($exam);
            $openAlerts = (int) ($alerts[$id] ?? 0);

            return [
                'profile' => $profile,
                'name' => $profile->user->name,
                'classroom' => $profile->classroom?->label(),
                'classroom_id' => $profile->classroom_id,
                'completion' => $completion[$id]['percentage'],
                'last_activity' => $lastActivity[$id],
                'inactive' => $this->progress->isInactive($lastActivity[$id], $profile->user->created_at),
                'qudurat' => $exam['qudurat'],
                'tahsili' => $exam['tahsili'],
                'next_exam' => $next,
                'booked_any' => $exam['qudurat']['booked'] || $exam['tahsili']['booked'],
                'follow_up' => $profile->follow_up_status,
                'open_alerts' => $openAlerts,
                'needs_follow_up' => $openAlerts > 0 || in_array($profile->follow_up_status, [FollowUpStatus::WATCH, FollowUpStatus::NEEDS_FOLLOWUP], true),
            ];
        })->sortBy('name')->values();
    }

    /**
     * Dashboard KPIs (brief §57) from roster rows.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{students: int, average_completion: int, needs_follow_up: int, upcoming_exams: int, average_improvement: ?float, not_booked: int, inactive: int}
     */
    public function kpis(Collection $rows): array
    {
        $upcomingDays = (int) $this->settings->get('upcoming_exam_alert_days');
        $improvements = $rows->pluck('qudurat.improvement')->merge($rows->pluck('tahsili.improvement'))->filter(fn ($v) => $v !== null);

        return [
            'students' => $rows->count(),
            'average_completion' => $rows->isEmpty() ? 0 : (int) round($rows->avg('completion')),
            'needs_follow_up' => $rows->where('needs_follow_up', true)->count(),
            'upcoming_exams' => $rows->filter(fn ($r) => $r['next_exam'] && $r['next_exam']['days'] <= $upcomingDays)->count(),
            'average_improvement' => $improvements->isEmpty() ? null : round($improvements->avg(), 1),
            'not_booked' => $rows->where('booked_any', false)->count(),
            'inactive' => $rows->where('inactive', true)->count(),
        ];
    }

    /**
     * Applies the roster filters (brief §57).
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function filter(Collection $rows, array $filters): Collection
    {
        $search = mb_strtolower(trim((string) ($filters['q'] ?? '')));

        return $rows
            ->when($search !== '', fn ($c) => $c->filter(fn ($r) => str_contains(mb_strtolower($r['name']), $search)
                || str_contains(mb_strtolower($r['profile']->student_code), $search)))
            ->when($filters['classroom_id'] ?? null, fn ($c, $id) => $c->where('classroom_id', (int) $id))
            ->when($filters['booking'] ?? null, fn ($c, $b) => $c->filter(fn ($r) => $b === 'booked' ? $r['booked_any'] : ! $r['booked_any']))
            ->when($filters['exam_type'] ?? null, fn ($c, $type) => $c->filter(fn ($r) => $r['next_exam'] && $r['next_exam']['attempt']->exam_type->value === $type))
            ->when($filters['activity'] ?? null, fn ($c, $a) => $c->filter(fn ($r) => $a === 'inactive' ? $r['inactive'] : ! $r['inactive']))
            ->when($filters['completion'] ?? null, fn ($c, $band) => $c->filter(fn ($r) => match ($band) {
                'low' => $r['completion'] < 40,
                'mid' => $r['completion'] >= 40 && $r['completion'] < 75,
                'high' => $r['completion'] >= 75,
                default => true,
            }))
            ->when($filters['follow_up'] ?? null, fn ($c, $status) => $status === 'attention'
                ? $c->where('needs_follow_up', true)
                : $c->filter(fn ($r) => $r['follow_up']->value === $status))
            ->when(isset($filters['score_below']) && $filters['score_below'] !== '', fn ($c) => $c->filter(
                fn ($r) => $r['qudurat']['best'] !== null && $r['qudurat']['best'] < (int) $filters['score_below']))
            ->values();
    }
}
