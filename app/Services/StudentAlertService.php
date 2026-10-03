<?php

namespace App\Services;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\AlertType;
use App\Enums\RoleName;
use App\Models\StudentAlert;
use App\Models\StudentProfile;
use App\Models\User;
use App\Support\ArabicDays;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Generates the automatic follow-up alerts (brief §58). Thresholds come from
 * settings: inactivity_days, upcoming_exam_alert_days, low_activity_threshold.
 *
 * Each alert has an identity (student, type, context key). A refresh:
 * - creates an alert when its condition holds and no alert with the same
 *   identity exists yet (so a counselor-resolved alert is not recreated);
 * - auto-resolves unresolved alerts whose condition no longer holds.
 * See docs/alerts.md.
 */
class StudentAlertService
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly StudentProgressService $progress,
        private readonly ExamProgressService $exams,
        private readonly AuditLogger $audit,
    ) {}

    public function refreshAll(): int
    {
        $created = 0;

        User::role(RoleName::STUDENT->value)->select('id')->chunkById(200, function ($students) use (&$created) {
            $created += $this->refresh($students->pluck('id')->all());
        });

        return $created;
    }

    public function refreshFor(User $student): int
    {
        return $this->refresh([$student->id]);
    }

    /**
     * @param  list<int>  $studentIds
     * @return int number of alerts created
     */
    public function refresh(array $studentIds): int
    {
        if ($studentIds === []) {
            return 0;
        }

        $conditions = $this->evaluate($studentIds);
        $created = 0;

        DB::transaction(function () use ($studentIds, $conditions, &$created) {
            $existing = StudentAlert::whereIn('student_id', $studentIds)->get()
                ->groupBy(fn (StudentAlert $a) => $a->student_id);

            foreach ($studentIds as $id) {
                $alerts = $existing->get($id, collect());
                $current = $conditions[$id] ?? [];
                $currentKeys = array_map(fn ($c) => $c['type']->value.'|'.$c['key'], $current);

                foreach ($current as $condition) {
                    $identity = $alerts->first(fn (StudentAlert $a) => $a->alert_type === $condition['type'] && $a->context_key === $condition['key']);

                    if ($identity === null) {
                        StudentAlert::create([
                            'student_id' => $id,
                            'alert_type' => $condition['type'],
                            'context_key' => $condition['key'],
                            'severity' => $condition['severity'],
                            'title' => $condition['type']->label(),
                            'message' => $condition['message'],
                            'status' => AlertStatus::OPEN,
                            'generated_at' => now(),
                        ]);
                        $created++;
                    } elseif ($identity->status !== AlertStatus::RESOLVED) {
                        $identity->update(['message' => $condition['message']]);
                    }
                }

                // Conditions that cleared: close their unresolved alerts automatically.
                $alerts->filter(fn (StudentAlert $a) => $a->status !== AlertStatus::RESOLVED
                    && ! in_array($a->alert_type->value.'|'.$a->context_key, $currentKeys, true))
                    ->each(fn (StudentAlert $a) => $a->update(['status' => AlertStatus::RESOLVED, 'resolved_at' => now(), 'resolved_by' => null]));
            }
        });

        return $created;
    }

    public function acknowledge(StudentAlert $alert): void
    {
        if ($alert->status === AlertStatus::OPEN) {
            $alert->update(['status' => AlertStatus::ACKNOWLEDGED]);
            $this->audit->record('alert.acknowledged', $alert, ['status' => 'open'], ['status' => 'acknowledged']);
        }
    }

    public function resolve(StudentAlert $alert, User $by): void
    {
        if ($alert->status !== AlertStatus::RESOLVED) {
            $old = $alert->status->value;
            $alert->update(['status' => AlertStatus::RESOLVED, 'resolved_at' => now(), 'resolved_by' => $by->id]);
            $this->audit->record('alert.resolved', $alert, ['status' => $old], ['status' => 'resolved']);
        }
    }

    /**
     * Which alert conditions currently hold, per student.
     *
     * @param  list<int>  $studentIds
     * @return array<int, list<array{type: AlertType, key: string, severity: AlertSeverity, message: string}>>
     */
    public function evaluate(array $studentIds): array
    {
        $upcomingDays = (int) $this->settings->get('upcoming_exam_alert_days');
        $lowThreshold = (int) $this->settings->get('low_activity_threshold');
        $inactivityDays = (int) $this->settings->get('inactivity_days');

        $users = User::whereIn('id', $studentIds)->get()->keyBy('id');
        $profiles = StudentProfile::with('classroom.grade')->whereIn('user_id', $studentIds)->get()->keyBy('user_id');
        $exams = $this->exams->summariesFor($studentIds);
        $lastActivity = $this->progress->lastActivityFor($studentIds);
        $weekActions = $this->progress->learningActionsSince($studentIds, CarbonImmutable::now()->subDays(7));

        $result = [];

        foreach ($studentIds as $id) {
            $user = $users->get($id);

            if ($user === null) {
                continue;
            }

            $conditions = [];
            $level = $profiles->get($id)?->classroom?->grade?->level;
            $isGrade12 = $level === null || $level === 12;

            foreach ($exams[$id] as $type => $exam) {
                $label = $exam['type']->label();

                if ($isGrade12 && ! $exam['booked']) {
                    $conditions[] = [
                        'type' => AlertType::NOT_BOOKED, 'key' => "{$type}", 'severity' => AlertSeverity::WARNING,
                        'message' => "لم تحجز الطالبة اختبار {$label} بعد.",
                    ];
                }

                $next = $exam['next'];
                $soon = $next !== null && $exam['days_until_next'] <= $upcomingDays;

                if ($soon && $weekActions[$id] < $lowThreshold) {
                    $conditions[] = [
                        'type' => AlertType::UPCOMING_LOW_ACTIVITY,
                        'key' => "{$type}:{$next->id}:".CarbonImmutable::now()->startOfWeek(CarbonImmutable::SUNDAY)->toDateString(),
                        'severity' => AlertSeverity::CRITICAL,
                        'message' => "اختبار {$label} ".ArabicDays::until($exam['days_until_next'])." ونشاطها هذا الأسبوع {$weekActions[$id]} فقط.",
                    ];
                }

                if ($soon && $exam['best'] !== null && $exam['best'] < $exam['target']) {
                    $conditions[] = [
                        'type' => AlertType::BELOW_TARGET, 'key' => "{$type}:{$next->id}", 'severity' => AlertSeverity::WARNING,
                        'message' => "أفضل درجة في {$label} {$exam['best']} والهدف {$exam['target']}، والاختبار ".ArabicDays::until($exam['days_until_next']).'.',
                    ];
                }

                if ($exam['improvement'] !== null && $exam['improvement'] > 0) {
                    $conditions[] = [
                        'type' => AlertType::IMPROVEMENT, 'key' => "{$type}:{$exam['latest_attempt']->id}", 'severity' => AlertSeverity::POSITIVE,
                        'message' => "تحسّنت درجة {$label} بمقدار ".ArabicDays::points($exam['improvement'])." (من {$exam['previous']} إلى {$exam['latest']}).",
                    ];
                }
            }

            if ($this->progress->isInactive($lastActivity[$id], $user->created_at)) {
                $since = $lastActivity[$id]?->toDateString() ?? 'never';
                $conditions[] = [
                    'type' => AlertType::INACTIVE, 'key' => $since, 'severity' => AlertSeverity::WARNING,
                    'message' => $lastActivity[$id]
                        ? "لا يوجد نشاط تعلّم منذ {$lastActivity[$id]->toDateString()} (أكثر من {$inactivityDays} أيام)."
                        : 'لم تسجّل الطالبة أي نشاط تعلّم بعد.',
                ];
            }

            $result[$id] = $conditions;
        }

        return $result;
    }
}
