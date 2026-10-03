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

## Logging (`email_logs`)

`App\Listeners\LogSentEmail` listens to `MessageSent` and writes **one row per recipient**:

- `recipient`, `subject`
- `template`: the Mailable or Notification class
- `user_id`: matched by recipient email, when one exists
- `provider_message_id`
- `status=sent`, `sent_at`

Recording failed sends (`status=failed` with `error_message`) is added together with queued notifications in Phase 7.

## Templates

A shared `<x-mail.layout>` RTL component arrives with the first real email in Phase 7.
