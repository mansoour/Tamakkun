<?php

namespace App\Enums;

enum ExamType: string
{
    case QUDURAT = 'qudurat';
    case TAHSILI = 'tahsili';

    public function label(): string
    {
        return match ($this) {
            self::QUDURAT => 'القدرات',
            self::TAHSILI => 'التحصيلي',
        };
    }
}
