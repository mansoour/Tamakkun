# Security

## Implemented in v0.1

| Area | Implementation |
|---|---|
| Authentication | Breeze (Blade). Login by username or email. Only `active` users can sign in. |
| Account status | `EnsureUserIsActive` (`active` middleware) signs out a user whose account is suspended or disabled mid-session. |
| Registration | Student self-registration at `/register`, controlled by the `student_registration` setting: **open** (active at once), **approval** (pending until an admin activates it) or **closed** (404). Students pick an active school, a grade of its current academic year and a classroom. The username doubles as the student code. Rate limit: 5 per minute and 20 per hour per IP. Audited as `student.self_registered`. Counselor and admin accounts are still created by an admin only. |
| External links | Every outbound link shows a privacy warning (`<x-external-link-notice>`): no real phone, ID, address or password on outside sites; use a dummy phone such as 0500000000. Official services marked «مصدر رسمي» are the exception. See docs/content-sources.md. |
| Forced password change | Whenever an admin sets a password (account creation, CSV import or a reset), the user gets `must_change_password = true`. While the admin setting `force_password_change` is on (default **on**, toggled at `/admin/settings`), `EnsurePasswordIsChanged` (`password.changed` middleware) sends that user to `/password/change` until they choose a new password, which must differ from the current one. A self-service email reset also clears the flag. |
| Status changes | `users.status` is not mass assignable. It changes only through explicit code (later the `AccountActivation` service, with audit). |
| Authorization | Permission-driven `can:` middleware on every area. Policies are added per feature. |
| Login rate limits | 5 failed attempts per username and IP pair locks that pair (`LoginRequest`), plus 20 requests per minute per IP on `POST /login` (`throttle:login`). |
| Password reset rate limit | 5 per minute per IP on `POST /forgot-password` and `POST /reset-password`. |
| Status disclosure | The "account not active" message is only shown after a correct password, so wrong passwords never reveal whether an account exists or is disabled. |
| CSRF | Laravel's default for every web form. |
| Passwords | bcrypt (`hashed` cast). |
| Output | Blade escaping (`{{ }}`). Unescaped output is not used for user data. |
| Audit | `audit_logs` via `AuditLogger`. Settings changes are already audited. |
| Sessions | `SESSION_SECURE_COOKIE=true` in production. HTTPS via CyberPanel SSL. |

## Security headers (`App\Http\Middleware\SecurityHeaders`)

| Header | Value |
|---|---|
| `X-Content-Type-Options` | `nosniff` |
| `X-Frame-Options` | `DENY` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Permissions-Policy` | camera, microphone, geolocation, payment and usb disabled |
| `Cross-Origin-Opener-Policy` | `same-origin` |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains`, HTTPS requests only |
| `Content-Security-Policy` | `default-src 'self'`; scripts `'self' 'unsafe-eval'`; styles `'self' 'unsafe-inline'` and Bunny Fonts; fonts `'self'` and Bunny Fonts; `frame-ancestors 'none'`; `object-src 'none'`; `form-action 'self'` |

CSP notes:

- `'unsafe-eval'` is required by the standard Alpine.js build, which evaluates directive expressions. Switching to `@alpinejs/csp` later would allow removing it.
- No inline `<script>` blocks are allowed. Keep JavaScript in `resources/js`.
- The policy is **enforced** in testing and production. In `local` it is sent as **Report-Only**, because Laravel Boost injects an inline browser-logging script. While `npm run dev` is running, no policy is sent, because Vite serves from another origin.
- When video embeds arrive (Phase 2), `frame-src` will list only the whitelisted providers.

## Uploads and external content (v0.3)

- Content thumbnails are validated (image, jpg/png/webp, at most 2 MB and 6000px) and then decoded and re-encoded to WebP by `ImageOptimizer`. A disguised file fails to decode, and SVG is rejected.
- Video URLs must be whitelisted YouTube or Vimeo links. CSP `frame-src` allows only `youtube-nocookie.com` and `player.vimeo.com`.
- Content, source and link URLs must be `https`, so `javascript:` and `http:` are rejected.
- External links open with `target="_blank" rel="noopener noreferrer"`.
- The lesson body is plain text, escaped and rendered with `nl2br(e(...))`. No HTML is accepted.
- Students only ever see visible content. Draft, scheduled and archived content pages return 404.

## Privacy of progress data (v0.4)

- Progress and favorites always apply to the signed-in student. There is no student ID in those routes, so one student cannot touch another's data.
- Counselors see a student's progress only through `StudentProfilePolicy` (assigned students).
- `activity_logs` holds only the minimal events listed in [progress.md](progress.md).

## Security review (v0.8)

| Check | Result |
|---|---|
| Routes without auth | Only `/`, the public pages (`/about`, `/resources`, `/privacy`, `/terms`, `/manifest.webmanifest`; v0.9), login, password reset, `/up` (health) and Boost's local-only browser log route |
| Authorization | Every area is gated by `can:` middleware. Record access goes through policies (students, exams, notes, alerts). Tests cover cross-student and cross-counselor denial |
| Mass assignment | `status`, `must_change_password` and `follow_up_status` are not fillable. Controllers pass only validated data |
| SQL | Eloquent bindings only. Search uses bound `like` parameters |
| XSS | Blade escaping everywhere. The one `{!! !!}` is `nl2br(e($body))` |
| Open redirect | Notification links are followed only when scheme and host match the site **exactly** (fixed in v0.8; the earlier prefix check allowed `site.com.evil.net`) |
| CSV injection | Formula-like cells are neutralised in exports (fixed in v0.8) |
| Uploads | Images are decoded and re-encoded to WebP. SVG is rejected. CSV import is validated and size-limited |
| Secrets in logs | Passwords never appear in audit or activity logs. Note text is never copied into audit logs |
| Rate limits | Login, password reset, challenge answers (30/min), quiz submissions (20/min) |
| Production caches | `php artisan optimize` (routes, config, views, events) succeeds; CI checks it |
| Dependencies | `composer audit` and `npm audit --omit=dev` run in CI. The only npm finding is a dev-only build dependency (`braces` via Tailwind 3's watcher), which never ships to users |

Server-side recommendations: set `expose_php = Off` in lsphp83's `php.ini`, keep `APP_DEBUG=false`, and keep `.env` at mode `600`.

## View as user (v0.9)

Brief §19, built read-only. `App\Services\ViewAsService` and `App\Http\Middleware\ViewAsUser`:

- Requires `users.view-as`. Only active accounts that cannot open the admin area can be viewed, so it never shows a more privileged account.
- The admin stays logged in; the session only stores whom they view. A yellow banner shows on every page with «إنهاء العرض».
- Only GET/HEAD requests are served as the viewed user. Any other request is refused with a notice, except ending the view and logging out (which also ends it).
- Each viewed request runs inside a database transaction that is always rolled back, so pages that record something on open (last viewed, notification read) leave no trace.
- `user.view-as-started` and `user.view-as-ended` are audited.

## Planned (later phases)

- Private storage for sensitive files.
- Rate limit on the contact form when it exists.
- Third-party API keys stored encrypted in the database (exception: `RESEND_API_KEY`, see [email.md](email.md)).

## Reporting

Report vulnerabilities privately to the project owner. Never open a public issue for them.
