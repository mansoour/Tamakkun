<?php

namespace App\Enums;

/**
 * Learning progression: تأسيس → تدريب → إتقان → مراجعة.
 */
enum ContentStage: string
{
    case FOUNDATION = 'foundation';
    case PRACTICE = 'practice';
    case MASTERY = 'mastery';
    case REVIEW = 'review';

    public function label(): string
    {
        return match ($this) {
            self::FOUNDATION => 'تأسيس',
            self::PRACTICE => 'تدريب',
            self::MASTERY => 'إتقان',
            self::REVIEW => 'مراجعة',
        };
    }
}
