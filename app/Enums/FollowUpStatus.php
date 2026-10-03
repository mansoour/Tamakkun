<?php

namespace App\Enums;

/**
 * Manual status set by the counselor — separate from automatic alerts.
 */
enum FollowUpStatus: string
{
    case NORMAL = 'normal';
    case WATCH = 'watch';
    case NEEDS_FOLLOWUP = 'needs_followup';
    case CONTACTED = 'contacted';
    case RESOLVED = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::NORMAL => 'طبيعي',
            self::WATCH => 'تحت الملاحظة',
            self::NEEDS_FOLLOWUP => 'تحتاج متابعة',
            self::CONTACTED => 'تم التواصل',
            self::RESOLVED => 'تمت المعالجة',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NORMAL, self::RESOLVED => 'gray',
            self::WATCH => 'warning',
            self::NEEDS_FOLLOWUP => 'danger',
            self::CONTACTED => 'brand',
        };
    }

    /**
     * Statuses listed on the follow-up page.
     *
     * @return list<self>
     */
    public static function active(): array
    {
        return [self::WATCH, self::NEEDS_FOLLOWUP, self::CONTACTED];
    }
}
