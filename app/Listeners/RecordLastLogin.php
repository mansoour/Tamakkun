<?php

namespace App\Listeners;

use App\Enums\ActivityEvent;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Login;

class RecordLastLogin
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function handle(Login $event): void
    {
        if ($event->user instanceof User) {
            $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
            $this->activity->log($event->user, ActivityEvent::LOGIN);
        }
    }
}
