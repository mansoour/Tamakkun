<?php

namespace App\Enums;

enum QuestionType: string
{
    case MULTIPLE_CHOICE = 'multiple_choice';
    case TRUE_FALSE = 'true_false';

    public function label(): string
    {
        return match ($this) {
            self::MULTIPLE_CHOICE => 'اختيار من متعدد',
            self::TRUE_FALSE => 'صح أو خطأ',
        };
    }
}
