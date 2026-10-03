<?php

namespace App\Support;

/**
 * Shared rules for usernames and student codes: Latin letters, digits,
 * dot, dash and underscore, so they are easy to type on any keyboard.
 */
class Username
{
    public const PATTERN = '/^[A-Za-z0-9._-]+$/';

    public const MAX = 64;

    public const CODE_MAX = 32;

    /**
     * @return list<string>
     */
    public static function rules(int $max = self::MAX): array
    {
        return ['regex:'.self::PATTERN, 'max:'.$max];
    }
}
