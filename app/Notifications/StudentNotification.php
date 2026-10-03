<?php

namespace App\Notifications;

use App\Services\SettingsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Base for platform notifications: always in-app (database), and also by
 * email when the recipient has an address and the admin setting
 * `email_notifications` is on. Queued so sending never slows a request
 * (brief §16). Sent emails are logged by LogSentEmail; failed deliveries by
 * LogFailedEmail.
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
        $channels = ['database'];

        if (! empty($notifiable->email) && app(SettingsService::class)->get('email_notifications')) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title().' — تمكّن')
            ->view('emails.notification', ['title' => $this->title(), 'body' => $this->body(), 'url' => $this->url()]);
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
