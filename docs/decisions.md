# Decisions log

Newest first. Each entry records what was decided and why.

## 2026-10-03 — v0.1 Foundation

1. **Website first, no native app.** A responsive Blade website. Services keep logic reusable for a future `/api/v1`.
2. **Login by username or email in one field.** Students use their username or student code, and email is optional for them. Counselors and admins may use either. National ID is never the username.
3. **Public registration removed.** Breeze's register routes, controller, view and test were deleted, because accounts are school-created. It can be reintroduced later behind a setting if requested.
4. **Profile is read-only plus password change.** Name, username and email are school-managed. Self-service profile edit and account deletion were removed.
5. **Account status is checked after the password.** An inactive account's message appears only for correct credentials, so status is never leaked to someone guessing passwords.
6. **Roles and permissions created by an idempotent migration**, not a seeder (production safety). Area access uses the permissions `student-area.access`, `counselor-area.access` and `admin-area.access`.
7. **Post-login redirect is permission-driven:** admin, then counselor, then student.
8. **Muted text colour darkened** from `#77717F` to `#6B6574` to meet WCAG AA on the lavender background.
9. **CSP enforced except locally,** where it is report-only because Laravel Boost injects an inline script. `'unsafe-eval'` is kept for the standard Alpine build.
10. **Settings defaults live in `config/tamakkun.php`.** The DB row wins. Unknown keys are rejected to catch typos.
11. **Backups cover the database and `storage/app` only.** Code is in Git, and `.env` is held by the server administrator.
12. **`laravel-lang/lang` 15.x requires the PHP `bcmath` extension.** It is listed in the requirements and enabled in CI.
13. **Planned sections are shown as disabled "قريبًا" items,** never as links, to honour "no fake buttons".
14. **The `.ai/guidelines/tamakkun.md` file feeds Laravel Boost,** which generates `CLAUDE.md`. The Laravel Cloud deployment guideline is excluded because production is CyberPanel.

## Roadmap (from the brief)

| Version | Scope |
|---|---|
| **v0.1** | Foundation/Auth ✅ |
| v0.2 | Users and school structure: schools, academic years, grades, classrooms, student/counselor profiles, assignment, `AccountActivation`, policies, CSV import |
| v0.3 | Learning content: sources, categories, subjects, chapters, topics, contents, videos, important links, content CRUD |
| v0.4 | Progress: start/complete, favorites, `StudentProgressService`, weekly progress, activity logs |
| v0.5 | Exam tracking: attempts, booking status, dates, scores, target, best/improvement, countdown |
| v0.6 | Counselor dashboard: KPIs, list and filters, student detail, notes, follow-up status, alerts |
| v0.7 | Challenge, motivation, announcements, notifications |
| v0.8 | Reports (mPDF/CSV), email via Resend, hardening |
| v0.9 | Pilot |
| v1.0 | Production |

## Open questions for the stakeholder

- Verified official URLs for **المفكر** and current ETEC service links. These stay `null` until verified, and none will be invented.
- Production domain name and the Resend sending domain.
- Off-server backup destination.
