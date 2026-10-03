<?php

namespace App\Enums;

enum AlertSeverity: string
{
    case INFO = 'info';
    case WARNING = 'warning';
    case CRITICAL = 'critical';
    case POSITIVE = 'positive';

    public function label(): string
    {
        return match ($this) {
            self::INFO => 'معلومة',
            self::WARNING => 'تنبيه',
            self::CRITICAL => 'عاجل',
            self::POSITIVE => 'إيجابي',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::INFO => 'brand',
            self::WARNING => 'warning',
            self::CRITICAL => 'danger',
            self::POSITIVE => 'success',
        };
    }

    /**
     * Severities that mean the student may need help.
     *
     * @return list<string>
     */
    public static function concerningValues(): array
    {
        return [self::WARNING->value, self::CRITICAL->value];
    }
}
