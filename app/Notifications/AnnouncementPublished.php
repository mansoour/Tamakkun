<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Support\Str;

class AnnouncementPublished extends StudentNotification
{
    public function __construct(public Announcement $announcement) {}

    protected function title(): string
    {
        return 'رسالة جديدة: '.$this->announcement->title;
    }

    protected function body(): string
    {
        return Str::limit($this->announcement->body, 140);
    }

    protected function url(): ?string
    {
        return route('student.dashboard').'#announcements';
    }

    protected function icon(): string
    {
        return 'megaphone';
    }
}
