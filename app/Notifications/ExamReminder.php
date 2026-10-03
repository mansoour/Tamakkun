<?php

namespace App\Notifications;

use App\Models\ExamAttempt;
use App\Support\ArabicDays;

class ExamReminder extends StudentNotification
{
    public function __construct(public ExamAttempt $attempt, public int $days) {}

    protected function title(): string
    {
        return "اختبار {$this->attempt->exam_type->label()} ".ArabicDays::until($this->days);
    }

    protected function body(): string
    {
        return 'راجعي خطتك وتأكدي من موعدك ومكان الاختبار في الموقع الرسمي.';
    }

    protected function url(): ?string
    {
        return route('student.exams.index');
    }

    public function key(): string
    {
        return "exam:{$this->attempt->id}:{$this->days}";
    }

    protected function icon(): string
    {
        return 'calendar-days';
    }
}
