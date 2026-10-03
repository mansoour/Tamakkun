<?php

namespace App\Enums;

/**
 * Meaningful student actions. Keep this list short: only record what the
 * platform needs for progress, streaks and counselor follow-up.
 */
enum ActivityEvent: string
{
    case LOGIN = 'login';
    case CONTENT_STARTED = 'content_started';
    case CONTENT_COMPLETED = 'content_completed';
    case CONTENT_UNCOMPLETED = 'content_uncompleted';

    public function label(): string
    {
        return match ($this) {
            self::LOGIN => 'تسجيل الدخول',
            self::CONTENT_STARTED => 'بدء محتوى',
            self::CONTENT_COMPLETED => 'إنجاز محتوى',
            self::CONTENT_UNCOMPLETED => 'التراجع عن إنجاز محتوى',
        };
    }

    /**
     * Learning actions count toward the activity streak; logging in alone does not.
     */
    public function countsAsLearning(): bool
    {
        return in_array($this, [self::CONTENT_STARTED, self::CONTENT_COMPLETED], true);
    }

    /**
     * @return list<string>
     */
    public static function learningValues(): array
    {
        return array_values(array_map(fn (self $e) => $e->value, array_filter(self::cases(), fn (self $e) => $e->countsAsLearning())));
    }
}
