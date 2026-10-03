<?php

namespace App\Enums;

enum AlertType: string
{
    case NOT_BOOKED = 'not_booked';
    case UPCOMING_LOW_ACTIVITY = 'upcoming_low_activity';
    case INACTIVE = 'inactive';
    case IMPROVEMENT = 'improvement';
    case BELOW_TARGET = 'below_target';

    public function label(): string
    {
        return match ($this) {
            self::NOT_BOOKED => 'لم تحجز',
            self::UPCOMING_LOW_ACTIVITY => 'اختبار قريب ونشاط منخفض',
            self::INACTIVE => 'غير نشطة',
            self::IMPROVEMENT => 'تحسّن في الدرجة',
            self::BELOW_TARGET => 'دون الهدف والاختبار قريب',
        };
    }
}
