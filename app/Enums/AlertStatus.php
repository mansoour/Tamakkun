<?php

namespace App\Enums;

enum AlertStatus: string
{
    case OPEN = 'open';
    case ACKNOWLEDGED = 'acknowledged';
    case RESOLVED = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'جديد',
            self::ACKNOWLEDGED => 'تم الاطلاع',
            self::RESOLVED => 'تمت المعالجة',
        };
    }

    /**
     * @return list<string>
     */
    public static function unresolvedValues(): array
    {
        return [self::OPEN->value, self::ACKNOWLEDGED->value];
    }
}
