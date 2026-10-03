<?php

namespace App\Listeners;

use App\Enums\EmailStatus;
use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Mail\Events\MessageSent;
use Symfony\Component\Mime\Address;

/**
 * Writes one `email_logs` row per recipient of every outgoing email.
 */
class LogSentEmail
{
    public function handle(MessageSent $event): void
    {
        $message = $event->sent->getOriginalMessage();
        $subject = method_exists($message, 'getSubject') ? $message->getSubject() : null;
        $template = $event->data['__laravel_mailable'] ?? $event->data['__laravel_notification'] ?? null;
        $providerId = $event->sent->getMessageId();

        foreach ($event->sent->getEnvelope()->getRecipients() as $recipient) {
            /** @var Address $recipient */
            $address = $recipient->getAddress();

            EmailLog::create([
                'user_id' => User::query()->where('email', $address)->value('id'),
                'recipient' => $address,
                'subject' => $subject,
                'template' => $template,
                'status' => EmailStatus::SENT,
                'provider_message_id' => $providerId,
                'sent_at' => now(),
            ]);
        }
    }
}
