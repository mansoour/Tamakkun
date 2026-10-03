<?php

namespace App\Enums;

enum ProgressStatus: string
{
    case NOT_STARTED = 'not_started';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::NOT_STARTED => 'لم يبدأ',
            self::IN_PROGRESS => 'قيد التقدم',
            self::COMPLETED => 'مكتمل',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NOT_STARTED => 'gray',
            self::IN_PROGRESS => 'warning',
            self::COMPLETED => 'success',
        };
    }
}
