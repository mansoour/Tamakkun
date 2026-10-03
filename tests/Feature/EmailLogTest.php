<?php

namespace Tests\Feature;

use App\Enums\EmailStatus;
use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_sent_email_is_logged(): void
    {
        Mail::raw('مرحبًا', function ($message) {
            $message->to('someone@example.com')->subject('اختبار');
        });

        $log = EmailLog::sole();
        $this->assertSame('someone@example.com', $log->recipient);
        $this->assertSame('اختبار', $log->subject);
        $this->assertSame(EmailStatus::SENT, $log->status);
        $this->assertNotNull($log->sent_at);
    }

    public function test_notification_emails_are_linked_to_the_user_and_template(): void
    {
        $user = User::factory()->counselor()->create(['email' => 'counselor@example.com']);

        $user->notify(new ResetPassword('token'));

        $log = EmailLog::sole();
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame(ResetPassword::class, $log->template);
    }
}
