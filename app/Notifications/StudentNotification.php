<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Base for in-app (database) notifications shown on /student/notifications.
 * Queued so creating many never slows a web request (brief §16). Email can
 * be added per notification in v0.8 by extending via().
 */
abstract class StudentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    abstract protected function title(): string;

    abstract protected function body(): string;

    abstract protected function url(): ?string;

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{title: string, body: string, url: ?string, icon: string, key: ?string}
     */
    public function toArray(object $notifiable): array
    {
        return ['title' => $this->title(), 'body' => $this->body(), 'url' => $this->url(), 'icon' => $this->icon(), 'key' => $this->key()];
    }

    /**
     * Optional de-duplication key so scheduled reminders are sent once.
     */
    public function key(): ?string
    {
        return null;
    }

    protected function icon(): string
    {
        return 'bell';
    }
}
