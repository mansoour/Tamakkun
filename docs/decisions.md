# Decisions log

Newest first. Each entry records what was decided and why.

## 2026-10-10 — v0.7 Challenge, motivation, announcements, notifications

1. **The challenge has one attempt per question**, only on its own date, and answers count toward the streak. Questions lock once anyone has answered, to keep results honest.
2. **The correct answer never reaches the browser early.** The model hides `is_correct`, and the view only reveals it after answering.
3. **The motivation of the day** is a dated item if present, otherwise a deterministic daily rotation, so it's the same for everyone all day.
4. **Announcement scope follows the role.** Admins address everyone; counselors address their own students, a class containing them, or one of them. A counselor's class announcement reaches the whole class.
5. **Notifications are in-app (database) and queued.** Email channels come with Resend in v0.8. Reminders are de-duplicated (`notified_at` and the notification `key`).
6. **Badges are computed, not stored**, are private to the student, and sit behind `enable_gamification`. There is no leaderboard (brief §56).
7. **The quiz engine (brief §54) is deferred.** The challenge tables cover v0.7's needs, and `ContentType::QUIZ` content can link to an external quiz until the engine is built.
8. **Challenge date uniqueness is checked by date** (`whereDate`), not the raw value, so it works on SQLite and MariaDB alike.

## 2026-10-09 — v0.6 Counselor dashboard

1. **Manual follow-up status and automatic alerts are separate** (brief §61). "Needs follow-up" combines both.
2. **Alerts have an identity** (student, type, context key), so they never duplicate, auto-resolve when the cause clears, and stay closed after a counselor resolves them until a new occurrence happens.
3. **Alerts refresh daily** (scheduler) and **immediately on exam changes**. Content progress does not trigger a refresh, to keep requests light; the daily run catches it.
4. **"Grade 12" means classroom grade level 12**, or no classroom (the platform targets Grade 12).
5. **"Not booked" is evaluated per exam type**, so a student booked for Qudurat but not Tahsili still gets a Tahsili alert.
6. **Notes are private by default.** Shared notes double as short messages to the student until announcements arrive (v0.7).
7. **The roster is built in memory from grouped queries.** This is ample for a school (hundreds of students). If a deployment grows much larger, move filters into SQL.
8. **Average improvement** averages the latest-minus-previous change across both exam types for students with at least two results.

## 2026-10-08 — v0.5 Exam tracking

1. **Students own their exam records** (the brief says the student enters dates and scores). Counselors can view but not edit them in this version.
2. **Scores are 0–100 integers** for both exams. A score is accepted only with "ظهرت النتيجة" (result received), and "taken" statuses cannot have future dates.
3. **The target is per exam type and takes the latest value the student set.** It falls back to the admin `default_target_score` (85), and the UI labels that case as the default target.
4. **Improvement compares the latest result with the previous one** (brief §58: "latest score > previous score"). Best score drives the gap to target.
5. **Score and date changes are audited even when the student makes them** (brief §15 lists them), and are also logged as student activity.
6. **No exam dates, fees or policies are invented.** Official booking and results go through admin-verified important links.

## 2026-10-07 — v0.4 Progress

1. **Progress is explicit.** Only ابدأ and أنجزت change it, never page views or external clicks (brief §52). An undo exists for accidental clicks.
2. **Completion is binary** (0% or 100%). There is no reliable signal for partial progress inside external videos, and faking one is not allowed (brief §70).
3. **Denominator = all visible content** in the section. Per-student assignment of content doesn't exist yet. When it does, the formula changes in one place (`StudentProgressService`).
4. **The week starts on Sunday** (Saudi school week) in Asia/Riyadh. The weekly goal is an admin setting (`weekly_content_goal`, default 6, the brief's "4/6" example).
5. **The streak survives until the end of the next day**, so it isn't broken in the morning before the student studies. Logging in doesn't count.
6. **Activity logging is minimal** (login and content start, complete and undo) to respect "do not over-collect".
7. **The counselor sees the same summary** on the student page now. Full follow-up tooling stays in v0.6.

## 2026-10-06 — v0.3 Learning content

1. **One general `contents` table** with a `section` (quantitative, verbal or tahsili). Qudurat content hangs off a category, and Tahsili content off subject, then optional chapter, then optional topic. Fields that don't apply to the section are cleared on save.
2. **The structure is seeded by an idempotent migration** from the stakeholder's lists (sources, 17 quantitative and 7 verbal categories, 4 subjects), so it exists in production. No provider URL is seeded, and no important links are seeded.
3. **The source-to-stage mapping** (المعاصر → تأسيس, and so on) is set per content item, not hard-coded.
4. **Videos are never hosted.** Only whitelisted YouTube or Vimeo URLs are accepted, embedded via `youtube-nocookie.com` or `player.vimeo.com`. iframe HTML is never accepted.
5. **The lesson body is plain text.** Rich text and HTML are deferred to avoid XSS and sanitiser complexity.
6. **Content slugs keep Arabic letters** (for example `/student/content/شرح-النسب`) for readable URLs, with a numeric suffix on clashes.
7. **The ابدأ / أنجزت (start / done) buttons are not shown yet.** Progress tracking is v0.4, so no fake buttons.
8. **`content.manage` goes to admins only by default.** The brief says counselors manage content "if permission exists", and it can be granted per role.
9. **Simple catalog screens share `CatalogController`** and generic views, to avoid six copies of the same CRUD code.

## 2026-10-05 — Stakeholder answers after v0.2

1. **Forced password change is admin-controlled.** The `force_password_change` setting (default on) is toggled at `/admin/settings`. The per-user flag is always recorded, so turning the setting back on takes effect immediately.
2. **Exactly one counselor per student** is confirmed.
3. **The import never creates structure** is confirmed.

## 2026-10-04 — v0.2 Users and school structure

1. **The hierarchy follows the brief:** school → academic year → grade → classroom. Grades belong to an academic year, so each new year gets fresh grades and classrooms, and old years remain as history.
2. **One assigned counselor per student** (`student_profiles.counselor_id`), as in the brief's import columns. The counselor must belong to the student's school.
3. **Student code ≠ national ID.** Codes and usernames are restricted to Latin letters, digits, `.`, `-` and `_`. Username defaults to the student code.
4. **Users are never deleted from the UI.** Accounts are suspended or disabled through `AccountActivation`, which keeps their history and audit trail.
5. **Hierarchy deletes are blocked** while children or students exist, both by database restrict foreign keys and by a friendly message.
6. **CSV import is all-or-nothing and queued.** A file with any invalid row cannot be confirmed. The import runs in a single transaction on the queue, because bcrypt for hundreds of rows exceeds web request limits on LiteSpeed. The validated payload is encrypted at rest and deleted afterwards.
7. **The import never creates structure** (schools, grades, classrooms or counselors), to avoid typos silently creating duplicates.
8. **Navigation hides links** the user lacks permission for. Area access and feature permissions are separate.
9. **Pagination** uses a custom Arabic view (`vendor/pagination/tamakkun`).

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
| **v0.2** | Users and school structure: schools, academic years, grades, classrooms, student/counselor profiles, assignment, `AccountActivation`, policies, CSV import ✅ |
| **v0.3** | Learning content: sources, categories, subjects, chapters, topics, contents, videos, important links, content CRUD ✅ |
| **v0.4** | Progress: start/complete, favorites, `StudentProgressService`, weekly progress, activity logs ✅ |
| **v0.5** | Exam tracking: attempts, booking status, dates, scores, target, best/improvement, countdown ✅ |
| **v0.6** | Counselor dashboard: KPIs, list and filters, student detail, notes, follow-up status, alerts ✅ |
| **v0.7** | Challenge, motivation, announcements, notifications ✅ |
| v0.8 | Reports (mPDF/CSV), email via Resend, hardening |
| v0.9 | Pilot |
| v1.0 | Production |

## Open questions for the stakeholder

- Verified official URLs for **المفكر** and current ETEC service links. These stay `null` until verified, and none will be invented.
- Production domain name and the Resend sending domain.
- Off-server backup destination.

