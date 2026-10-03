<?php

namespace App\Notifications;

class DailyChallengeAvailable extends StudentNotification
{
    protected function title(): string
    {
        return 'لديك تحدي جديد';
    }

    protected function body(): string
    {
        return 'تحدي اليوم جاهز. سؤالان سريعان يحافظان على سلسلة أيامك.';
    }

    protected function url(): ?string
    {
        return route('student.challenge');
    }

    protected function icon(): string
    {
        return 'bolt';
    }
}
