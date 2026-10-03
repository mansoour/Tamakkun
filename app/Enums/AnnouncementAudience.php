<?php

namespace App\Enums;

/**
 * Who an announcement is for. `all` is platform-wide (admins only);
 * `my_students` is every student assigned to the author (counselors).
 */
enum AnnouncementAudience: string
{
    case ALL = 'all';
    case MY_STUDENTS = 'my_students';
    case CLASSROOM = 'classroom';
    case STUDENT = 'student';

    public function label(): string
    {
        return match ($this) {
            self::ALL => 'جميع الطالبات',
            self::MY_STUDENTS => 'طالباتي المسندات',
            self::CLASSROOM => 'فصل محدد',
            self::STUDENT => 'طالبة محددة',
        };
    }
}
