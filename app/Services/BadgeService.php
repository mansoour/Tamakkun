<?php

namespace App\Services;

use App\Enums\ContentStage;
use App\Enums\ExamBookingStatus;
use App\Enums\ProgressStatus;
use App\Models\Content;
use App\Models\ExamAttempt;
use App\Models\StudentContentProgress;
use App\Models\User;

/**
 * Personal, positive badges (brief §56), computed from real data — nothing
 * is stored and there is no public ranking. Shown only while the
 * `enable_gamification` setting is on.
 */
class BadgeService
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly StudentProgressService $progress,
        private readonly ExamProgressService $exams,
    ) {}

    public function enabled(): bool
    {
        return (bool) $this->settings->get('enable_gamification');
    }

    /**
     * @return list<array{key: string, label: string, description: string, icon: string, earned: bool}>
     */
    public function for(User $student): array
    {
        $completed = StudentContentProgress::where('student_id', $student->id)->where('status', ProgressStatus::COMPLETED);
        $foundationTotal = Content::visible()->where('stage', ContentStage::FOUNDATION)->count();
        $foundationDone = (clone $completed)->whereHas('content', fn ($q) => $q->visible()->where('stage', ContentStage::FOUNDATION))->count();
        $improvements = collect($this->exams->summary($student))->pluck('improvement')->filter(fn ($v) => $v !== null);

        return [
            $this->badge('streak_7', '7 أيام متواصلة', 'نشاط تعلّم سبعة أيام متتالية', 'bolt', $this->progress->streak($student) >= 7),
            $this->badge('lessons_10', '10 دروس مكتملة', 'أنجزتِ عشرة دروس أو مقاطع', 'academic-cap', (clone $completed)->count() >= 10),
            $this->badge('first_exam', 'أول اختبار', 'سجّلتِ أول نتيجة اختبار', 'flag',
                ExamAttempt::where('student_id', $student->id)->where('booking_status', ExamBookingStatus::RESULT_RECEIVED)->exists()),
            $this->badge('improved_10', 'تحسن 10 درجات', 'تحسّنت درجتك 10 درجات أو أكثر', 'arrow-trending-up', $improvements->contains(fn ($v) => $v >= 10)),
            $this->badge('foundation_done', 'إكمال مرحلة التأسيس', 'أنجزتِ كل محتوى التأسيس المتاح', 'trophy', $foundationTotal > 0 && $foundationDone >= $foundationTotal),
        ];
    }

    /**
     * @return array{key: string, label: string, description: string, icon: string, earned: bool}
     */
    private function badge(string $key, string $label, string $description, string $icon, bool $earned): array
    {
        return compact('key', 'label', 'description', 'icon', 'earned');
    }
}
