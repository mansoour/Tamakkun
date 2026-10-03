<?php

namespace App\Services;

use App\Enums\ExamType;
use App\Enums\ProgressStatus;
use App\Models\ChallengeAnswer;
use App\Models\Content;
use App\Models\StudentContentProgress;
use App\Models\User;
use App\Support\ArabicDays;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * The reports of brief §64, scoped to the students the viewer may see
 * (assigned students for counselors, everyone with students.view-all).
 * Every report returns plain columns and rows so it can be shown on screen,
 * exported to CSV or rendered to PDF identically.
 */
class ReportService
{
    public const REPORTS = [
        'student-progress' => ['تقدّم الطالبات', 'نسبة الإنجاز وآخر نشاط وحالة المتابعة لكل طالبة.'],
        'class-progress' => ['تقدّم الفصول', 'متوسطات الإنجاز والحجز والدرجات لكل فصل.'],
        'qudurat-scores' => ['درجات القدرات', 'آخر وأفضل درجة والهدف والمتبقي لكل طالبة.'],
        'tahsili-scores' => ['درجات التحصيلي', 'آخر وأفضل درجة والهدف والمتبقي لكل طالبة.'],
        'improvement' => ['التحسّن', 'الفرق بين آخر نتيجتين لكل اختبار.'],
        'upcoming-exams' => ['الاختبارات القادمة', 'الاختبارات المحجوزة القادمة مرتبة حسب الأقرب.'],
        'not-booked' => ['لم يحجزن', 'الطالبات اللاتي لم يحجزن اختبارًا.'],
        'inactive' => ['غير النشطات', 'الطالبات بلا نشاط تعلّم خلال المدة المحددة في الإعدادات.'],
        'content-completion' => ['إنجاز المحتوى', 'عدد ونسبة الطالبات اللاتي أنجزن كل عنصر محتوى.'],
        'challenge-participation' => ['المشاركة في التحدي', 'إجابات تحدي اليوم خلال آخر 30 يومًا.'],
    ];

    public function __construct(
        private readonly StudentRosterService $roster,
        private readonly SettingsService $settings,
    ) {}

    /**
     * @return array{key: string, title: string, description: string, columns: list<string>, rows: list<list<string|int|float|null>>, generated_at: Carbon}
     */
    public function build(string $key, User $viewer): array
    {
        if (! isset(self::REPORTS[$key])) {
            throw new InvalidArgumentException("Unknown report [{$key}].");
        }

        $rows = $this->roster->rows($viewer);
        [$columns, $data] = match ($key) {
            'student-progress' => $this->studentProgress($rows),
            'class-progress' => $this->classProgress($rows),
            'qudurat-scores' => $this->scores($rows, ExamType::QUDURAT),
            'tahsili-scores' => $this->scores($rows, ExamType::TAHSILI),
            'improvement' => $this->improvement($rows),
            'upcoming-exams' => $this->upcoming($rows),
            'not-booked' => $this->notBooked($rows),
            'inactive' => $this->inactive($rows),
            'content-completion' => $this->contentCompletion($rows),
            'challenge-participation' => $this->challengeParticipation($rows),
        };

        return [
            'key' => $key,
            'title' => self::REPORTS[$key][0],
            'description' => self::REPORTS[$key][1],
            'columns' => $columns,
            'rows' => array_values($data),
            'generated_at' => now(),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function studentProgress(Collection $rows): array
    {
        return [
            ['الطالبة', 'رقم الطالبة', 'الفصل', 'الإنجاز %', 'آخر نشاط', 'حالة المتابعة', 'تنبيهات مفتوحة'],
            $rows->map(fn ($r) => [
                $r['name'], $r['profile']->student_code, $r['classroom'] ?? '—', $r['completion'],
                $r['last_activity']?->format('Y-m-d') ?? '—', $r['follow_up']->label(), $r['open_alerts'],
            ])->all(),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function classProgress(Collection $rows): array
    {
        return [
            ['الفصل', 'عدد الطالبات', 'متوسط الإنجاز %', 'محجوز', 'غير نشطات', 'متوسط أفضل قدرات'],
            $rows->groupBy(fn ($r) => $r['classroom'] ?? 'بدون فصل')->map(function (Collection $group, string $classroom) {
                $best = $group->pluck('qudurat.best')->filter(fn ($v) => $v !== null);

                return [
                    $classroom, $group->count(), (int) round($group->avg('completion')),
                    $group->where('booked_any', true)->count(), $group->where('inactive', true)->count(),
                    $best->isEmpty() ? '—' : round($best->avg(), 1),
                ];
            })->sortKeys()->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function scores(Collection $rows, ExamType $type): array
    {
        $key = $type->value;

        return [
            ['الطالبة', 'الفصل', 'آخر درجة', 'أفضل درجة', 'الهدف', 'المتبقي للهدف', 'التحسن'],
            $rows->filter(fn ($r) => $r[$key]['attempts']->isNotEmpty())->map(fn ($r) => [
                $r['name'], $r['classroom'] ?? '—', $r[$key]['latest'] ?? '—', $r[$key]['best'] ?? '—',
                $r[$key]['target'], $r[$key]['gap'] ?? '—', $this->signed($r[$key]['improvement']),
            ])->all(),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function improvement(Collection $rows): array
    {
        $data = [];

        foreach ($rows as $r) {
            foreach (ExamType::cases() as $type) {
                $exam = $r[$type->value];

                if ($exam['improvement'] !== null) {
                    $data[] = [$r['name'], $type->label(), $exam['previous'], $exam['latest'], $exam['improvement']];
                }
            }
        }

        usort($data, fn ($a, $b) => $b[4] <=> $a[4]);

        return [
            ['الطالبة', 'الاختبار', 'السابقة', 'الأخيرة', 'التحسن'],
            array_map(fn ($row) => [...array_slice($row, 0, 4), $this->signed($row[4])], $data),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function upcoming(Collection $rows): array
    {
        return [
            ['الطالبة', 'الفصل', 'الاختبار', 'التاريخ', 'المتبقي', 'الإنجاز %'],
            $rows->filter(fn ($r) => $r['next_exam'])->sortBy(fn ($r) => $r['next_exam']['days'])->map(fn ($r) => [
                $r['name'], $r['classroom'] ?? '—', $r['next_exam']['attempt']->exam_type->label(),
                $r['next_exam']['attempt']->exam_date->format('Y-m-d'), ArabicDays::until($r['next_exam']['days']), $r['completion'],
            ])->all(),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function notBooked(Collection $rows): array
    {
        return [
            ['الطالبة', 'رقم الطالبة', 'الفصل', 'لم تحجز'],
            $rows->filter(fn ($r) => ! $r['qudurat']['booked'] || ! $r['tahsili']['booked'])->map(fn ($r) => [
                $r['name'], $r['profile']->student_code, $r['classroom'] ?? '—',
                collect(ExamType::cases())->reject(fn ($t) => $r[$t->value]['booked'])->map->label()->join('، '),
            ])->all(),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function inactive(Collection $rows): array
    {
        return [
            ['الطالبة', 'الفصل', 'آخر نشاط تعلّم', 'أيام بلا نشاط'],
            $rows->where('inactive', true)->map(fn ($r) => [
                $r['name'], $r['classroom'] ?? '—', $r['last_activity']?->format('Y-m-d') ?? 'لا يوجد',
                $r['last_activity'] ? (int) $r['last_activity']->diffInDays(now()) : '—',
            ])->all(),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function contentCompletion(Collection $rows): array
    {
        $ids = $rows->pluck('profile.user_id')->all();
        $total = max(1, count($ids));
        $completed = StudentContentProgress::whereIn('student_id', $ids)->where('status', ProgressStatus::COMPLETED)
            ->selectRaw('content_id, count(*) as total')->groupBy('content_id')->pluck('total', 'content_id');

        return [
            ['المحتوى', 'القسم', 'المرحلة', 'أنجزته', 'النسبة %'],
            Content::visible()->ordered()->get()->map(fn (Content $c) => [
                $c->title, $c->section->label(), $c->stage?->label() ?? '—',
                (int) ($completed[$c->id] ?? 0), (int) floor(($completed[$c->id] ?? 0) * 100 / $total),
            ])->all(),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function challengeParticipation(Collection $rows): array
    {
        $answers = ChallengeAnswer::whereIn('student_id', $rows->pluck('profile.user_id'))
            ->where('answered_at', '>=', now()->subDays(30))
            ->selectRaw('student_id, count(*) as total, sum(case when is_correct then 1 else 0 end) as correct')
            ->groupBy('student_id')->get()->keyBy('student_id');

        return [
            ['الطالبة', 'الفصل', 'الإجابات', 'الصحيحة', 'نسبة الصحة %'],
            $rows->map(function ($r) use ($answers) {
                $a = $answers->get($r['profile']->user_id);
                $total = (int) ($a?->total ?? 0);
                $correct = (int) ($a?->correct ?? 0);

                return [$r['name'], $r['classroom'] ?? '—', $total, $correct, $total ? (int) floor($correct * 100 / $total) : '—'];
            })->sortByDesc(fn ($row) => $row[2])->values()->all(),
        ];
    }

    private function signed(?int $value): string
    {
        return $value === null ? '—' : ($value > 0 ? '+'.$value : (string) $value);
    }
}
