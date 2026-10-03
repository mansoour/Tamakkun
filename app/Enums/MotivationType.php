<?php

namespace App\Enums;

enum MotivationType: string
{
    case TEXT = 'text';
    case VIDEO = 'video';
    case IMAGE = 'image';
    case TIP = 'tip';
    case TASK = 'task';
    case PRE_EXAM = 'pre_exam';
    case STUDY_HABIT = 'study_habit';

    public function label(): string
    {
        return match ($this) {
            self::TEXT => 'رسالة قصيرة',
            self::VIDEO => 'مقطع',
            self::IMAGE => 'صورة',
            self::TIP => 'نصيحة',
            self::TASK => 'مهمة 15 دقيقة',
            self::PRE_EXAM => 'تذكير قبل الاختبار',
            self::STUDY_HABIT => 'عادة مذاكرة',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::VIDEO => 'play-circle',
            self::IMAGE => 'sparkles',
            self::TIP => 'light-bulb',
            self::TASK => 'clock',
            self::PRE_EXAM => 'flag',
            self::STUDY_HABIT => 'check-badge',
            default => 'sparkles',
        };
    }
}
