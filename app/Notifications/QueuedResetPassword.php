<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Arabic password-reset email, sent through the queue (brief §16).
 */
class QueuedResetPassword extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public function toMail($notifiable): MailMessage
    {
        $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('إعادة تعيين كلمة المرور — تمكّن')
            ->view('emails.notification', [
                'title' => 'إعادة تعيين كلمة المرور',
                'body' => "وصلنا طلب لإعادة تعيين كلمة مرور حسابك. الرابط صالح لمدة {$minutes} دقيقة. إن لم تطلبي ذلك فتجاهلي هذه الرسالة.",
                'url' => $this->resetUrl($notifiable),
                'action' => 'تعيين كلمة مرور جديدة',
            ]);
    }
}
