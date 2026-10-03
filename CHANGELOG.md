# Changelog

All notable changes to Tamakkun are documented here. Versions follow the roadmap in `docs/decisions.md`.

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
