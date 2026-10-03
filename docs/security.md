# Security

## Implemented in v0.1

| Area | Implementation |
|---|---|
| Authentication | Breeze (Blade). Login by username or email. Only `active` users can sign in. |
| Account status | `EnsureUserIsActive` (`active` middleware) signs out a user whose account is suspended or disabled mid-session. |
| Registration | Public self-registration is **removed**. Accounts are created by the school. |
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

## Planned (later phases)

- `ImageOptimizer` for uploads: MIME, extension, size and dimension checks, WebP conversion, no unsanitised SVG.
- Private storage for sensitive files.
- Read-only "view as user" for admins, with a banner and audit entry.
- Rate limit on the contact form when it exists.
- Third-party API keys stored encrypted in the database (exception: `RESEND_API_KEY`, see [email.md](email.md)).

## Reporting

Report vulnerabilities privately to the project owner. Never open a public issue for them.
