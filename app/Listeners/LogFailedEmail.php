<?php

namespace App\Listeners;

use App\Enums\EmailStatus;
use App\Models\EmailLog;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Queue\Events\JobFailed;
use Throwable;

/**
 * Writes `email_logs` rows with status "failed" when a queued email job
 * fails permanently — queued notifications on the mail channel and
 * queued mailables. Successful sends are logged by LogSentEmail.
 */
class LogFailedEmail
{
    public function handle(JobFailed $event): void
    {
        try {
            $command = unserialize($event->job->payload()['data']['command'] ?? '');
        } catch (Throwable) {
            return;
        }

        $error = mb_substr($event->exception->getMessage(), 0, 1000);

        if ($command instanceof SendQueuedNotifications && in_array('mail', (array) $command->channels, true)) {
            foreach ($command->notifiables as $notifiable) {
                if (! empty($notifiable->email)) {
                    $this->log($notifiable->email, $notifiable->id ?? null, get_class($command->notification), $error);
                }
            }
        }

        if ($command instanceof SendQueuedMailable) {
            foreach ($command->mailable->to as $recipient) {
                $this->log($recipient['address'], null, get_class($command->mailable), $error);
            }
        }
    }

    private function log(string $recipient, ?int $userId, string $template, string $error): void
    {
        EmailLog::create([
            'user_id' => $userId,
            'recipient' => $recipient,
            'template' => $template,
            'status' => EmailStatus::FAILED,
            'error_message' => $error,
        ]);
    }
}
