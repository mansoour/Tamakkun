# Testing

```bash
vendor/bin/phpunit                    # whole suite
vendor/bin/phpunit --filter=AreaAccess
php artisan test                      # same suite, nicer output
```

- **Database:** SQLite in memory (`phpunit.xml`). Every feature test uses `RefreshDatabase`, so each test starts with an empty, freshly migrated database, including the roles and permissions created by migrations.
- **Environment:** `APP_ENV=testing`, `APP_LOCALE=ar`, array cache, mail and session, sync queue.
- **Style:** feature tests call real routes (`$this->get('/student/dashboard')`). Use factories, never real data.
- **Rule:** every behaviour change ships with a test.

## Factories

```php
User::factory()->student()->create();      // username like st12345, no email, student role
User::factory()->counselor()->create();
User::factory()->admin()->create();
User::factory()->withStatus(UserStatus::DISABLED)->create();
User::factory()->create();                 // active user with no role or permissions
StudentProfile::factory()->inClassroom($classroom)->assignedTo($counselor)->create();
CounselorProfile::factory()->forSchool($school)->create();
Classroom::factory()->create();            // also creates grade → current academic year → school
```

## Current coverage

| File | Covers |
|---|---|
| `Auth/AuthenticationTest` | Username and email login, wrong password, unknown user, inactive statuses blocked, lockout after 5 failures, per-IP limit, logout, mid-session disable, registration removed, status not mass assignable |
| `AreaAccessTest` | Guests redirected, per-role landing page, cross-area 403s, permission-driven (not role-driven) access, no-permission 403 |
| `RolesAndPermissionsTest` | Migrations create every enum role and permission, with Arabic labels |
| `SecurityHeadersTest` | Headers present, CSP enforced vs report-only, HSTS only on HTTPS, password reset rate limit |
| `SettingsServiceTest` | Defaults, typed values, cache cleared on save, audit log written, unknown keys rejected |
| `EmailLogTest` | Every sent email logged, user and template linked |
| `LayoutTest` | `lang="ar" dir="rtl"`, home page copy, planned sections not links, `<x-icon>` rendering, accessibility and unknown-name failure |
| `ProfileTest` | Account page shows school-managed data, self-edit and delete disabled |
| `Admin/SchoolStructureTest` | Create, update and delete schools, years, grades and classrooms. Unique names, a single current year, deletion blocked with children, audit entries, permission checks |
| `Admin/StudentManagementTest` | Create a student who can then log in, pending until activated, classroom and counselor must match the school, uniqueness, counselor reassignment audited with no password in logs, no self-disable, search and filters, counselor management, real dashboard counts |
| `Admin/StudentImportTest` | Preview, confirm and import; Arabic headers and BOM; Windows-1256; semicolons; per-row errors block confirmation; missing columns; full rollback; non-CSV rejected; template download; permission |
| `Counselor/AssignedStudentsTest` | Only assigned students are listed and viewable, 403 for others, view-all policy, dashboard counts |
| `Admin/ContentManagementTest` | Create video content, unique slugs, video whitelist (no iframe or other hosts), required URLs per type, category and section match, Tahsili subject → chapter → topic consistency, WebP thumbnails, SVG and disguised files rejected, publish lifecycle audited, list filters, permission |
| `Admin/ContentCatalogTest` | Seeded structure without invented URLs, sources, categories unique per section, Tahsili hierarchy, important links (https only), blocked deletes, permissions |
| `Student/LearningAreasTest` | Only visible content per section, hidden categories, stage and source filters, privacy-friendly embed and escaped body, 404 for drafts, Tahsili grouping, video library, links page and official badge, empty states, student-area permission, CSP `frame-src` |
| `Student/ContentProgressTest` | Viewing doesn't start progress; start, complete and undo (logged once); per-student isolation; hidden content is 404; non-students forbidden; favorites toggle and list (visible only); status badges; login activity |
| `Student/ProgressSummaryTest` | Zero state; visible-only overall and per-section %; archived drops out; weekly vs the admin goal (Sunday week); streak incl. the yesterday rule; missed day and login don't count; dashboard and progress page numbers; counselor view; goal validation |
| `Student/ExamTrackingTest` | Add a booked exam and see the countdown; numbering per type; multiple scores (latest, best, improvement, gap); default target; next exam; date and score validation; score dropped unless the result is in; update audited and logged; type is immutable; no access to other students' exams; delete; counselor view (assigned only); counselors can't use student routes |
| `Unit/ArabicDaysTest` | Arabic day and point count forms |
| `Counselor/StudentAlertsTest` | Each alert type and its thresholds, no duplicates, auto-resolve, counselor-resolved alerts not recreated, Grade 12 only, exam changes refresh immediately, daily command |
| `Counselor/FollowUpTest` | Real KPIs, assigned-only roster and filters, all 8 tabs, audited follow-up status, private-by-default notes never shown to students, student and unassigned-counselor denial, author-only note delete, acknowledge and resolve alerts, exams and results pages |
| `Engagement/DailyChallengeTest` | Answer hidden until answered, correct and wrong feedback with streak, one attempt, foreign option rejected, past or unpublished blocked, admin authoring (MC and true/false), validation incl. date uniqueness, lock after answers, permissions, counselor tab |
| `Engagement/MotivationTest` | Dated item wins, stable rotation, hidden inactive and future items, whitelisted video, WebP image, permissions |
| `Engagement/AnnouncementTest` | Admin to all, counselor to own students only, class and student targeting, counselor restrictions, scheduled dispatch exactly once, expired and withdrawn hidden |
| `Engagement/NotificationsTest` | Morning reminders once (challenge, 7-day and 1-day exam), page, bell, open and mark read, no access to others' notifications, badges follow the setting |
| `Unit/VideoEmbedTest` | Supported YouTube and Vimeo forms; rejects http, other or lookalike hosts, iframe HTML, bad IDs and `javascript:` |
| `Auth/*` (Breeze) | Email verification, password confirmation, reset and update |
