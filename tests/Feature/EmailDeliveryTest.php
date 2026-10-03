<?php

namespace Tests\Feature;

use App\Enums\EmailStatus;
use App\Models\EmailLog;
use App\Models\User;
use App\Notifications\DailyChallengeAvailable;
use App\Notifications\QueuedResetPassword;
use App\Services\SettingsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Tests\TestCase;
use Throwable;

class EmailDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_are_emailed_and_logged_when_the_user_has_an_address(): void
    {
        $user = User::factory()->counselor()->create(['email' => 'counselor@example.com']);

        $user->notify(new DailyChallengeAvailable);

        $log = EmailLog::sole();
        $this->assertSame('counselor@example.com', $log->recipient);
        $this->assertSame(EmailStatus::SENT, $log->status);
        $this->assertSame('لديك تحدي جديد — تمكّن', $log->subject);
        $this->assertSame(1, $user->notifications()->count(), 'in-app copy is stored too');
    }

    public function test_no_email_without_an_address_or_when_disabled(): void
    {
        User::factory()->student()->create()->notify(new DailyChallengeAvailable);

        app(SettingsService::class)->set('email_notifications', false);
        User::factory()->counselor()->create(['email' => 'x@example.com'])->notify(new DailyChallengeAvailable);

        $this->assertDatabaseCount('email_logs', 0);
    }

    public function test_email_uses_the_rtl_arabic_layout(): void
    {
        $html = (new DailyChallengeAvailable)->toMail(User::factory()->make(['email' => 'a@example.com']))->render();

        $this->assertStringContainsString('dir="rtl"', $html);
        $this->assertStringContainsString('تحدي اليوم جاهز', $html);
    }

    public function test_password_reset_email_is_queued_and_arabic(): void
    {
        $user = User::factory()->counselor()->create(['email' => 'reset@example.com']);
        $notification = new QueuedResetPassword('token-123');

        $this->assertInstanceOf(ShouldQueue::class, $notification);
        $mail = $notification->toMail($user);
        $this->assertSame('إعادة تعيين كلمة المرور — تمكّن', $mail->subject);
        $this->assertStringContainsString('token-123', $mail->render());
    }

    public function test_failed_deliveries_are_logged_as_failed(): void
    {
        Mail::extend('broken', fn () => new class extends AbstractTransport
        {
            protected function doSend(SentMessage $message): void
            {
                throw new \RuntimeException('SMTP connection refused');
            }

            public function __toString(): string
            {
                return 'broken://';
            }
        });
        config(['mail.mailers.broken' => ['transport' => 'broken'], 'mail.default' => 'broken']);

        $user = User::factory()->counselor()->create(['email' => 'fail@example.com']);

        try {
            $user->notify(new DailyChallengeAvailable);
        } catch (Throwable) {
            // The sync queue rethrows after the job is marked as failed.
        }

        $log = EmailLog::where('status', EmailStatus::FAILED)->sole();
        $this->assertSame('fail@example.com', $log->recipient);
        $this->assertSame($user->id, $log->user_id);
        $this->assertStringContainsString('SMTP connection refused', $log->error_message);
    }
}
