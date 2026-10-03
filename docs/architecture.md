# Architecture

Tamakkun is a server-rendered Laravel 13 website. Pages are Blade templates, light interactivity uses Alpine.js 3, styling is Tailwind CSS 3, and Vite 8 builds the assets. There is no SPA, React or Vue.

## Principles

- **Thin controllers.** Controllers read the request, call a service and return a view or redirect.
- **Services hold business logic** (`app/Services`). Blade files, Alpine components and model accessors stay simple.
- **Form Requests** validate input, with Arabic messages (`lang/ar` from `laravel-lang/lang`).
- **Enums** (`app/Enums`) hold fixed lists. Each has an Arabic `label()`.
- **Authorization is permission-driven**: `can:` middleware and Policies check `App\Enums\PermissionName`, never role names. See [roles-permissions.md](roles-permissions.md).
- **Settings in the database**, cached and read through `SettingsService`.
- **API-ready.** Because logic lives in services, a future `/api/v1` for a mobile app can reuse it without rewriting anything.

## Directory guide

| Path | Contents |
|---|---|
| `app/Enums` | `UserStatus`, `RoleName`, `PermissionName`, `EmailStatus`, `ImportStatus` |
| `app/Services` | `SettingsService`, `AuditLogger`, `DashboardRedirector`, `AccountActivation`, `SchoolMembershipService`, `SchoolStructureService`, `StudentImportService`, `DashboardMetricsService` |
| `app/Policies` | `StudentProfilePolicy` |
| `app/Jobs` | `ImportStudents` (queued CSV import) |
| `app/Http/Middleware` | `SecurityHeaders` (global web), `EnsureUserIsActive` (alias `active`) |
| `app/Http/Controllers/{Student,Counselor,Admin}` | Area controllers |
| `app/Listeners` | `LogSentEmail` (writes `email_logs`), `RecordLastLogin` |
| `app/Support/Navigation.php` | Sidebar items per area. Items with `route => null` are planned sections |
| `app/View/Components/AppLayout.php` | Authenticated shell (picks the area's navigation) |
| `config/icons.php` | Heroicons outline path data |
| `config/tamakkun.php` | Default values for every setting |
| `resources/views/components` | Reusable Blade components |
| `resources/views/{student,counselor,admin}` | Area pages |

## Request flow after login

1. `POST /login` → `LoginRequest` finds the user by **username** (or **email** if the input is an email), checks the password, then checks that `status` is `active`.
2. The user is redirected to `/dashboard`. `DashboardRedirectController` asks `DashboardRedirector` for the first area the user may access: admin, then counselor, then student. A user with no area permission gets a 403.
3. Area routes are grouped under `/student`, `/counselor` and `/admin`. Each group uses `auth`, `active` and `can:<area permission>`.

## Services (v0.2)

| Service | Responsibility |
|---|---|
| `AccountActivation` | The only code that changes `users.status`. Audits every change and refuses to let users suspend themselves. |
| `SchoolMembershipService` | Creates and updates student and counselor accounts with their profiles in one transaction. Audits `user.created`, `student.created`, `student.updated`, `student.assigned_to_counselor`, `counselor.*`. Password changes are recorded only as "changed". |
| `SchoolStructureService` | Create, update and delete for schools, academic years, grades and classrooms. Audits each change, keeps one current year per school, and blocks deleting a level that has children. |
| `StudentImportService` | CSV parse, validate, preview, confirm and run. See [imports.md](imports.md). |
| `DashboardMetricsService` | Real counts for the admin and counselor dashboards. |
| `ContentService` | Create and update content (slug, section clean-up, minutes to seconds, thumbnail), plus publish, unpublish, archive and restore. All audited. |
| `ContentStructureService` | Sources, categories, subjects, chapters, topics and important links. Audited, and deletion is blocked while content depends on the record. |
| `ContentCompletionService` | Start, complete and undo for the signed-in student. Writes progress rows and activity logs. Viewing never advances progress. |
| `FavoriteService` | Toggle and check favorites |
| `StudentProgressService` | **The only place progress numbers are calculated** (completion, sections, weekly, streak). See [progress.md](progress.md). |
| `ActivityLogger` | Writes `activity_logs` for student learning actions and logins |
| `ImageOptimizer` | Decodes uploaded raster images, resizes them to at most 1280px wide, re-encodes them as WebP (which strips metadata) and stores them on the `public` disk. SVG is never accepted. |

`App\Support\VideoEmbed` turns a YouTube or Vimeo URL into a privacy-friendly embed URL and rejects everything else.

`App\Http\Controllers\Admin\CatalogController` is a small base class for simple list, create, edit and delete screens (sources, categories, subjects, chapters, topics, links). Each subclass declares its fields and columns and keeps its own Form Request. The views are `admin/catalog/{index,form}`.

## Settings

Table `settings` (`key`, `value` stored as JSON). Use the service:

```php
$settings = app(\App\Services\SettingsService::class);
$days = $settings->get('inactivity_days');   // stored value, else config default
$settings->set('inactivity_days', 10);        // saves, clears cache, writes audit log
```

- Defaults and the list of allowed keys live in `config/tamakkun.php`. Unknown keys are rejected.
- Admins edit settings at `/admin/settings` (`settings.manage`). Only settings that already change behaviour are shown there; currently `force_password_change` and `weekly_content_goal`.
- The whole table is cached forever under `settings.all`. Saving through the service clears that cache.
- Current keys: `platform_name`, `tagline`, `default_target_score`, `inactivity_days`, `upcoming_exam_alert_days`, `low_activity_threshold`, `enable_gamification`, `enable_guardian_accounts`, `force_password_change`, `weekly_content_goal`, `support_email`, `support_phone`, `privacy_url`, `terms_url`.

## Logging foundations

- `audit_logs`: important admin/counselor actions, written with `AuditLogger::record($action, $model, $old, $new)`. Action names are dotted, for example `settings.updated`.
- `email_logs`: one row per recipient of every sent email (see [email.md](email.md)).
- `activity_logs` (student behaviour) arrives in Phase 3.
- Application logs: `daily` channel, 14 days, `warning` level in production.

## Design system

The design language comes from the approved mockup: purple gradient, lavender background, white rounded cards, soft shadows, and a youthful feel. It is adapted to a real responsive website, not a stretched phone frame.

| Token | Tailwind | Value |
|---|---|---|
| Primary | `brand-600` | `#7458B5` |
| Secondary | `brand-400` | `#9B83D1` (decorative and gradient only, not for text) |
| Background | `canvas` / `brand-50` | `#F7F4FB` |
| Surface | `surface` | `#FFFFFF` |
| Text | `ink` | `#302B3A` |
| Muted text | `muted` | `#6B6574` |
| Border | `line` / `brand-200` | `#E9E2F2` |

- **Contrast:** the brief's muted `#77717F` reaches only 4.33:1 on the lavender background, below WCAG AA. It was darkened to `#6B6574` (5.16:1). Primary `#7458B5` has 5.5:1 against white.
- **Fonts:** headings use Alexandria (`font-heading`), body text uses IBM Plex Sans Arabic (`font-sans`), both loaded from Bunny Fonts. PDFs will use fonts bundled in `resources/fonts` and never Bunny Fonts.
- **Helpers** (`resources/css/app.css`): `.card`, `.btn-primary`, `.btn-secondary`, `.btn-ghost`. Buttons are at least 44px tall for touch.
- **RTL:** every page uses `<html lang="ar" dir="rtl">`. Use logical classes (`ms-`, `me-`, `ps-`, `pe-`, `start-`, `end-`, `border-e`). Usernames and emails are shown with `dir="ltr"`.

### Responsive layout

| Width | Behaviour |
|---|---|
| < 1024px (phones, tablets) | Sticky top bar with menu, brand and profile. Navigation opens in a drawer (focus-trapped, closes with Esc). One-column content. |
| ≥ 1024px | Fixed sidebar on the start (right) side, content column up to 72rem wide. |

Supported widths: 360, 375, 390, 414, 768, 1024, and 1280px and up.

### Components

| Component | Purpose |
|---|---|
| `<x-icon name label?>` | Heroicon. Decorative by default (`aria-hidden`). With `label` it gets `role="img"`. An unknown name throws an error. |
| `<x-brand size inverted>` | Logo mark and "تمكّن" wordmark |
| `<x-password-input>` | Password field with a show/hide toggle (Alpine, accessible) |
| `<x-form.input name label type hint>` | Label, input and error, wired with `aria-invalid` and `aria-describedby` |
| `<x-alert type title>` | info / success / warning / danger |
| `<x-badge color>` | brand / gray / success / warning / danger |
| `<x-dev-notice>` | Labels a page as a development shell |
| `<x-page-header title description>` + `actions` slot | Page title row |
| `<x-table>` + `head` slot | Card table that scrolls horizontally on small screens |
| `<x-empty-state icon title description>` | Empty list message |
| `<x-stat-card label value icon>` | KPI tile |
| `<x-form.select>`, `<x-form.checkbox>` | Labelled form controls with errors |
| `<x-delete-button action>` | DELETE form with a confirmation dialog |
| `<x-content-card :content>` | Student content tile (type icon or thumbnail, stage, duration, source) |
| `<x-source-badge :source>` | Source name badge |
| `<x-student-progress-card :summary>` | Four KPI tiles: completion, week, streak, in progress |
| `<x-progress-bar label percentage>` | Accessible progress bar (`role=progressbar`) |
| `<x-flash>` | Session `success` message and `delete`/`account_status`/`import` errors. Included in the app layout |
| `<x-text-input>`, `<x-input-label>`, `<x-input-error>`, `<x-primary-button>`, `<x-auth-session-status>` | Restyled Breeze components |

**No fake buttons.** Sections planned for later phases appear in the navigation as grey, non-clickable items with a "قريبًا" badge (`aria-disabled`). Dashboard shells show `<x-dev-notice>` until real data exists.
