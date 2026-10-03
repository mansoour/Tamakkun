# Tamakkun (تمكّن) project rules

Tamakkun is an Arabic-first, RTL, responsive Laravel website for Grade 12 female students preparing for Qudurat and Tahsili, plus their school counselors and admins. The full product brief lives in `docs/` (start with `docs/architecture.md` and `docs/decisions.md`).

## Stack (do not replace)
- PHP 8.3, Laravel 13, MariaDB/MySQL (SQLite in-memory for tests).
- Server-rendered Blade + Alpine.js 3 + Tailwind CSS 3 (+ forms, typography) built with Vite 8. No React, Vue or SPA routing.
- Icons: Heroicons paths in `config/icons.php`, rendered with `<x-icon name="..." />`. No emoji icons.
- Fonts: Alexandria 700/800 for headings (`font-heading`; `h1` uses 800), IBM Plex Sans Arabic 400/500/700 for body. Never use `font-semibold` or other weights that are not loaded.
- Every page is `<html lang="ar" dir="rtl">`. Use logical Tailwind classes (`ms-`, `me-`, `ps-`, `pe-`, `start-`, `end-`), never `ml-`/`mr-`/`left-`/`right-` unless unavoidable.

## Architecture
- Thin controllers. Business logic lives in `app/Services` (or actions).
- Validation in Form Request classes with Arabic messages/attributes.
- Fixed lists are PHP enums in `app/Enums` with an Arabic `label()` method.
- Authorization is permission-driven (`can:` middleware, Policies, `App\Enums\PermissionName`). Never check role names to authorize.
- New permissions are added by a migration (idempotent `findOrCreate`), never by re-running a seeder.
- Admin-changeable values belong in the `settings` table via `App\Services\SettingsService`, not hard-coded.
- Important admin/counselor actions must be written with `App\Services\AuditLogger`.
- Emails/notifications go through queues; every sent email is logged in `email_logs`.
- Uploaded images must go through the ImageOptimizer (WebP); never accept unsanitised SVG.
- Never fabricate real-world content: provider/ETEC URLs, exam policy, partnerships or real student data. Leave URLs null until verified.
- Do not build buttons that do nothing. Unbuilt sections are labelled "قريبًا" and are not links.

## Workflow
- A test is required for every behaviour change. Feature tests call real routes. Run `vendor/bin/phpunit`.
- Run `vendor/bin/pint --dirty` before every commit.
- Update `docs/` and `CHANGELOG.md` with every meaningful change. This project rule overrides any generic guideline saying documentation should only be written on request.
- Production is a CyberPanel/OpenLiteSpeed VPS (see `docs/deployment.md`), not Laravel Cloud.
- Commit messages start with `feat:`, `fix:` or `chore:`. No "Co-authored-by: Claude" lines.
- Never commit `.env`, `vendor/`, `node_modules/`, `public/build/`, backups, database dumps or `.claude/`.
- Daily work on `develop`; `main` is fast-forwarded to `develop` and is what production runs.
