# Email

## Environments

| Environment | `MAIL_MAILER` | Behaviour |
|---|---|---|
| Local | `log` | Emails are written to `storage/logs/laravel.log`. Nothing is sent. |
| Tests | `array` | Kept in memory. |
| Production | `resend` | Sent through Resend (`resend/resend-php`). |

## Resend setup (production)

1. Add the sending domain in Resend.
2. Add the DNS records Resend shows: **SPF**, **DKIM** and a **DMARC** policy (start with `p=none`, then tighten).
3. Wait for verification, then set `MAIL_MAILER=resend`, `RESEND_API_KEY` and `MAIL_FROM_ADDRESS` (an address on the verified domain).

### Why `RESEND_API_KEY` is in `.env` (documented exception)

The project rule is that API keys are stored encrypted in the database and edited in admin screens. The mail transport is different: Laravel builds it from configuration when the app boots, before database-backed settings can be read safely. Keeping this one key in `.env` (permissions `600`) is the accepted exception.

## Queues

Emails and notifications must be queued (`ShouldQueue`), never sent synchronously inside a web request. The queue worker runs under systemd (see [deployment.md](deployment.md#queue-worker-systemd)).

## What is emailed (v0.8)

- **Platform notifications** (`StudentNotification` subclasses: announcements, daily challenge, exam reminders) go to the in-app inbox **and** by email when the recipient has an email address and the admin setting **إرسال الإشعارات بالبريد أيضًا** (`email_notifications`, default on) is enabled. Most students have no email, so this mainly reaches counselors and admins.
- **Password reset** (`QueuedResetPassword`), in Arabic and queued.
- Every email uses the RTL layout `<x-mail.layout>` (tables and inline styles, email-safe), via `resources/views/emails/notification.blade.php`.

## Logging (`email_logs`)

`App\Listeners\LogSentEmail` listens to `MessageSent` and writes **one row per recipient**:

- `recipient`, `subject`
- `template`: the Mailable or Notification class
- `user_id`: matched by recipient email, when one exists
- `provider_message_id`
- `status=sent`, `sent_at`

**Failures:** `App\Listeners\LogFailedEmail` listens to `JobFailed`. When a queued notification job on the `mail` channel, or a queued mailable, fails permanently, it writes `status=failed` with the `error_message` for each recipient. Admins see both outcomes at `/admin/email-logs`.

## Templates

`<x-mail.layout>` (`resources/views/components/mail/layout.blade.php`) is the shared RTL email layout. Add new emails as views that use it.

## Testing locally

With `MAIL_MAILER=log`, emails are written to `storage/logs/laravel.log` and still logged in `email_logs`. Run `php artisan queue:work` to process queued mail.
