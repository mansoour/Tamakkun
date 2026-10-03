<?php

namespace App\Enums;

enum ExamBookingStatus: string
{
    case NOT_BOOKED = 'not_booked';
    case BOOKED = 'booked';
    case COMPLETED = 'completed';
    case RESULT_PENDING = 'result_pending';
    case RESULT_RECEIVED = 'result_received';

    public function label(): string
    {
        return match ($this) {
            self::NOT_BOOKED => 'لم تحجز',
            self::BOOKED => 'تم الحجز',
            self::COMPLETED => 'تم الاختبار',
            self::RESULT_PENDING => 'بانتظار النتيجة',
            self::RESULT_RECEIVED => 'ظهرت النتيجة',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NOT_BOOKED => 'warning',
            self::BOOKED => 'brand',
            self::COMPLETED, self::RESULT_PENDING => 'gray',
            self::RESULT_RECEIVED => 'success',
        };
    }

    /**
     * Statuses that describe an exam that has already taken place.
     */
    public function isTaken(): bool
    {
        return in_array($this, [self::COMPLETED, self::RESULT_PENDING, self::RESULT_RECEIVED], true);
    }
}
