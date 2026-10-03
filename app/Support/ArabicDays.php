<?php

namespace App\Support;

/**
 * Grammatical Arabic phrases for counts: days (اليوم، غدًا، بعد يومين…)
 * and score points (درجة واحدة، درجتان، 3 درجات، 11 درجة).
 */
class ArabicDays
{
    public static function until(int $days): string
    {
        return match (true) {
            $days < 0 => 'مضى الموعد',
            $days === 0 => 'اليوم',
            $days === 1 => 'غدًا',
            $days === 2 => 'بعد يومين',
            $days <= 10 => "بعد {$days} أيام",
            default => "بعد {$days} يومًا",
        };
    }

    public static function points(int $count): string
    {
        $count = abs($count);

        return match (true) {
            $count === 1 => 'درجة واحدة',
            $count === 2 => 'درجتين',
            $count >= 3 && $count <= 10 => "{$count} درجات",
            default => "{$count} درجة",
        };
    }
}
