# Changelog

All notable changes to Tamakkun are documented here. Versions follow the roadmap in `docs/decisions.md`.

## [v0.7.0] — 2026-10-10 — Challenge, motivation, announcements, notifications

### Added
- تحدي اليوم: daily multiple-choice or true/false questions, one attempt, instant feedback with explanation and streak, history, admin authoring (locked once answered), counselor challenges tab.
- دفعة اليوم: tips, 15-minute tasks, habits, pre-exam reminders, video and image items with dated or rotating selection, plus admin management.
- Announcements from admins (all, class, student) and counselors (own students, class, student), with scheduling, expiry, withdrawal, dashboard display and audit.
- Queued in-app notifications (bell, unread count, notifications page) for announcements, today's challenge and exam reminders 7 days and 1 day before. New scheduled commands: `tamakkun:send-reminders` and `tamakkun:dispatch-announcements`.
- Personal badges (no ranking) behind the new **تفعيل الأوسمة** setting.
- The student dashboard shows announcements, the challenge status and today's motivation.
- Permissions: `challenges.manage`, `motivations.manage`, `announcements.manage-all`, `announcements.send`.
- Docs: `docs/engagement.md`, plus updates to the schema, architecture, permissions, testing, deployment and decisions docs.

## [v0.6.0] — 2026-10-09 — Counselor dashboard

### Added
- Counselor dashboard KPIs: students, average completion, needs follow-up, upcoming exams, average improvement, not booked and inactive. Also the needs-follow-up list, upcoming exams and latest alerts.
- Counselor roster with every column from the brief and filters (class, booking, exam type, activity, completion, follow-up status, score, search), built by `StudentRosterService` with grouped queries.
- Tabbed student page: overview, scores, exams, activity, content, challenges (placeholder), notes and alerts.
- Manual follow-up status (audited), private-by-default counselor notes (shared notes appear on the student dashboard), and author-only note delete.
- Automatic alerts (`StudentAlertService`): not booked, upcoming exam with low activity, inactive, improvement, below target near the exam. Thresholds come from settings, alerts never duplicate and auto-resolve, and acknowledge and resolve are audited. Refreshed daily (`tamakkun:refresh-alerts`) and on exam changes.
- Pages: تحتاج متابعة, التنبيهات, الاختبارات القادمة, النتائج.
- `students.follow-up` permission (migration). Policies for notes and alerts.
- Docs: `docs/alerts.md`, plus updates to the schema, architecture, permissions, testing, deployment and decisions docs.

### Fixed
- Signed numbers such as "+6" now display correctly inside RTL text.

## [v0.5.0] — 2026-10-08 — Exam tracking

### Added
- موعدي ودرجتي: students add, edit and delete Qudurat and Tahsili attempts with booking status, date, score, target and notes. Attempt numbers are assigned automatically.
- `ExamProgressService`: latest, best and previous score, improvement, target (or admin default), gap to target, next exam and days remaining.
- Next-exam countdown and per-exam score cards on the student dashboard. The counselor student page shows exams and attempts read-only.
- `ExamAttemptPolicy` (owner edits; assigned counselor and admins view). Exam changes are audited and logged as activity.
- Arabic grammar helper for day and point counts. Components `x-exam-countdown` and `x-score-summary`. Demo exam data.
- Docs: `docs/exams.md`, plus updates to the schema, architecture, permissions, testing and decisions docs.

### Changed
- Badges no longer wrap inside table cells on small screens.

## [v0.4.0] — 2026-10-07 — Progress

### Added
- ابدأ, أنجزت and undo on content pages (`ContentCompletionService`). Viewing never changes progress.
- `StudentProgressService`: overall and per-section completion, weekly goal (Sunday week), learning streak, in-progress count and last activity, all from one place.
- تقدمي page, المفضلة page with a favorite toggle, and progress status badges on content cards.
- Student dashboard with real progress cards and "أكملي من حيث توقفتِ".
- Counselor student page shows the student's progress.
- `activity_logs` for login and content start, complete and undo (`ActivityLogger`).
- Admin setting `weekly_content_goal`.
- Components `x-student-progress-card` and `x-progress-bar`. Demo progress for the demo student.
- Docs: `docs/progress.md`, plus updates to the schema, architecture, security, testing and decisions docs.

## [v0.3.0] — 2026-10-06 — Learning content

### Added
- Content model with sections (quantitative, verbal, Tahsili), types, stages, difficulty, scheduled publishing and archiving.
- Sources, quantitative and verbal categories, and the Tahsili tree (subject → chapter → topic), seeded from the stakeholder brief by an idempotent migration. No URLs are invented.
- Admin content management with dependent category, subject, chapter and topic selects, filters, and publish, unpublish, archive and restore actions. All audited.
- Admin screens for sources, categories, subjects, chapters, topics and important links, sharing `CatalogController`.
- `ImageOptimizer`: thumbnails are re-encoded to WebP, resized and stripped of metadata. SVG is rejected.
- Video whitelist (`VideoEmbed`): YouTube (via youtube-nocookie) and Vimeo only, with a matching CSP `frame-src`.
- Student pages: القدرات الكمي and القدرات اللفظي (grouped by category, with stage, source and search filters), التحصيلي (subject → chapter → topic), مكتبة المقاطع, روابط مهمة (with a مصدر رسمي badge) and a content page.
- The student dashboard now links to the learning areas.
- New permissions `content.manage` and `links.manage`, added by migration.
- Fake demo lessons in the seeder.
- Docs: `docs/content-sources.md`, plus updates to the schema, permissions, architecture, security, testing and decisions docs.

## [v0.2.1] — 2026-10-05

### Added
- Forced password change: accounts whose password was set by an admin (creation, import or reset) must choose a new password at next login.
- Admin settings page (`/admin/settings`, `settings.manage` permission, added by migration) with the **إلزام تغيير كلمة المرور** switch. Changes are audited.

## [v0.2.0] — 2026-10-04 — Users and school structure

### Added
- School structure: schools, academic years (one current per school), grades and classrooms, with admin create, edit and delete. Deletion is blocked while children exist.
- Student and counselor profiles. Each student is assigned to one counselor of the same school.
- Admin pages for students and counselors, with search and filters (school, status, unassigned) and dependent classroom and counselor selects.
- `AccountActivation` service and a status control for pending, active, suspended and disabled accounts, with an audit entry for every change.
- `SchoolMembershipService` and `SchoolStructureService`, with audit logging for creates, updates, counselor assignment and deletes. Passwords are never logged.
- CSV student import: upload, validate, preview with per-row errors, confirm, queued all-or-nothing import, summary. Supports Arabic headers, the Excel BOM, Windows-1256 and semicolon delimiters, and offers a template download.
- New permissions (`schools.manage`, `users.manage`, `students.view-all`, `students.view-assigned`, `students.import`), added by migration.
- `StudentProfilePolicy`: counselors see only their assigned students.
- Counselor "طالباتي" list and student summary page.
- Real counts on the admin and counselor dashboards (`DashboardMetricsService`).
- Components: `x-page-header`, `x-table`, `x-empty-state`, `x-stat-card`, `x-form.select`, `x-form.checkbox`, `x-delete-button`, `x-flash`. Arabic pagination.
- Demo seeder now builds a fake school, year, grade, two classrooms, two counselors and 18 students.
- Docs: `docs/imports.md`, plus updates to the schema, permissions, architecture, testing and decisions docs.

### Fixed
- Screen-reader-only table headers could widen the page on phones.

## [v0.1.0] — 2026-10-03 — Foundation/Auth

### Added
- Laravel 13 / PHP 8.3 project, MariaDB-ready configuration, SQLite in-memory tests.
- Arabic-first RTL base layouts (`lang="ar" dir="rtl"`) with the Tamakkun design system (purple gradient, lavender canvas, rounded cards), Alexandria and IBM Plex Sans Arabic fonts via Bunny Fonts.
- Tailwind CSS 3 (forms, typography), Alpine.js 3 (with the focus plugin) and Vite 8.
- Heroicons in `config/icons.php` and the `<x-icon>` component, plus `<x-brand>`, `<x-password-input>`, `<x-form.input>`, `<x-alert>`, `<x-badge>` and `<x-dev-notice>`.
- Breeze (Blade) authentication in Arabic, logging in by username or student code, or by email.
- Account status (`pending`, `active`, `suspended`, `disabled`). Inactive accounts cannot log in and are signed out mid-session.
- `spatie/laravel-permission` with student, counselor and admin roles and area permissions, created by an idempotent migration.
- Permission-aware redirect after login. Development-only dashboard shells for the student, counselor and admin areas, with responsive sidebar and drawer navigation.
- Read-only account page with password change.
- Security headers middleware (CSP, X-Frame-Options, HSTS over HTTPS and more), plus login and password-reset rate limiting.
- `settings` table with a cached `SettingsService` that audits changes.
- `audit_logs` and `email_logs` tables. Every sent email is logged.
- `spatie/laravel-backup` configured for encrypted nightly database and upload backups.
- Fake-only demo seeder, Arabic translations (`laravel-lang/lang`), Laravel Boost guidelines, Pint, and a GitHub Actions CI workflow.
- Documentation: README and the `docs/` folder.

### Removed
- Public self-registration, self-service profile editing and account deletion (accounts are school-managed).
