<?php

namespace App\Enums;

enum ContentDifficulty: string
{
    case BEGINNER = 'beginner';
    case INTERMEDIATE = 'intermediate';
    case ADVANCED = 'advanced';

    public function label(): string
    {
        return match ($this) {
            self::BEGINNER => 'مبتدئ',
            self::INTERMEDIATE => 'متوسط',
            self::ADVANCED => 'متقدم',
        };
    }
}
