<?php

namespace App\Enums;

enum EmailStatus: string
{
    case SENT = 'sent';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::SENT => 'أُرسل',
            self::FAILED => 'فشل الإرسال',
        };
    }
}
