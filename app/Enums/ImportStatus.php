<?php

namespace App\Enums;

enum ImportStatus: string
{
    case PREVIEWED = 'previewed';
    case QUEUED = 'queued';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PREVIEWED => 'بانتظار التأكيد',
            self::QUEUED => 'جارٍ الاستيراد',
            self::COMPLETED => 'اكتمل الاستيراد',
            self::FAILED => 'فشل الاستيراد',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PREVIEWED, self::QUEUED => 'brand',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
        };
    }
}
