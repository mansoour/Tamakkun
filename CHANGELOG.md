# Changelog

All notable changes to Tamakkun are documented here. Versions follow the roadmap in `docs/decisions.md`.

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
