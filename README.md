# Tamakkun (تمكّن)

> خطوتك اليوم… تصنع نتيجتك غدًا
> استعداد • تدريب • متابعة • إنجاز

**Tamakkun** is a responsive, Arabic-first (RTL) website for Grade 12 female students preparing for the **Qudurat** (القدرات العامة, General Aptitude Test) and **Tahsili** (التحصيلي, Achievement Test) exams, and for the school counselors who follow their progress.

- **Students** (mostly on mobile browsers) get structured quantitative, verbal and Tahsili resources, videos, official links, daily challenges, motivation, exam-date and score tracking, and personal progress.
- **Counselors** (mostly on desktop/tablet) monitor assigned students, identify who needs follow-up, review scores and activity, manage content, send announcements and run reports.
- **Admins** manage schools, classes, users, permissions, content and settings.

The site is built website-first. A native mobile app may come later, so business logic lives in services that a future `/api/v1` can reuse.

**Current version: v0.9 — Completion and polish.** See [CHANGELOG.md](CHANGELOG.md) and the roadmap in [docs/decisions.md](docs/decisions.md).

---

## Tech stack

| Layer | Technology |
|---|---|
| Language / framework | PHP 8.3, Laravel 13 |
| Database | MariaDB/MySQL (local: MariaDB 10.4 via XAMPP · server: MariaDB 10.3) |
| Tests | PHPUnit 12 on SQLite in memory |
| Views | Blade (server-rendered), Alpine.js 3, Tailwind CSS 3 (`forms`, `typography`) |
| Build | Vite 8 |
| Fonts | Alexandria 700/800 (headings and display), IBM Plex Sans Arabic 400/500/700 (body and UI) via Bunny Fonts |
| Icons | Heroicons, SVG paths in `config/icons.php`, rendered with `<x-icon name="…" />` |
| Auth / permissions | Laravel Breeze (Blade), `spatie/laravel-permission` |
| Other | `spatie/laravel-backup`, `mpdf/mpdf`, `resend/resend-php`, `laravel-lang/lang` (Arabic), Laravel Boost, Pint, Pail |

No React, no Vue, no SPA.

---

## Requirements

- PHP **8.3** with extensions: `bcmath`, `ctype`, `curl`, `fileinfo`, `gd`, `intl`, `mbstring`, `openssl`, `pdo_mysql`, `pdo_sqlite`, `tokenizer`, `xml`, `zip`
- Composer 2
- Node.js 22+ and npm
- MariaDB 10.3+ or MySQL 8

### PHP path note (developer PC)

Use the standalone PHP 8.3 at:

```text
C:\php\php.exe
```

Do **not** use the older PHP that ships with XAMPP. Check with `php -v` (or call `C:\php\php.exe` directly) before running any command below.

The full step-by-step Windows guide, including which `php.ini` extensions to enable and a troubleshooting table, is in **[docs/windows-setup.md](docs/windows-setup.md)**.

### XAMPP / MySQL note

XAMPP is used **only** for MariaDB/MySQL. Do not serve the site through XAMPP Apache. Run Laravel with `php artisan serve` instead.

Create the local database once (phpMyAdmin or the MySQL shell):

```sql
CREATE DATABASE tamakkun CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

---

## Local setup

```bash
git clone git@github.com:mansoour/Tamakkun.git
cd Tamakkun
git checkout develop

composer install
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate
```

### `.env`

`.env.example` is preconfigured for XAMPP's MariaDB:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tamakkun
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=log               # never send real email locally
APP_LOCALE=ar
APP_TIMEZONE=Asia/Riyadh
```

`.env` only holds what the app needs before it can boot. Everything an admin may want to change lives in the `settings` table (see [docs/architecture.md](docs/architecture.md#settings)). Never commit `.env`.

### Migrations and seeders

```bash
php artisan migrate           # creates tables, roles, permissions and the seeded content structure
php artisan storage:link      # serves uploaded thumbnails
php artisan db:seed           # local/testing only: fake demo users
# or both from scratch:
php artisan migrate:fresh --seed
```

Roles and permissions are created by **migrations**, not seeders, so seeding can never reset production role assignments.

### Frontend (npm)

```bash
npm install
npm run dev                   # Vite dev server with hot reload, keep it running
```

Before deploying, build the assets with `npm run build`. The output goes to `public/build`, which is generated and never committed.

### Check and run the site

```bash
php artisan tamakkun:doctor   # checks PHP, extensions, database, migrations, storage and assets
php artisan serve             # http://localhost:8000
```

### Demo credentials (local only)

`php artisan db:seed` creates these **fake** accounts. All passwords are `password`.

| Role | Login (username or email) |
|---|---|
| Admin | `admin` or `admin@tamakkun.test` |
| Counselor | `counselor` or `counselor@tamakkun.test` |
| Second counselor | `counselor2` |
| Student | `student` |
| Disabled student (to test the block) | `disabled-student` |

The seeder refuses to run in production. For a real install, create the first admin with `php artisan tamakkun:create-admin`.

Students can also create their own account at `/register` (school → grade → classroom). An admin controls this under **الإعدادات ← تسجيل الطالبات**: open, approval or closed.

### Laravel Boost (Claude Code)

`CLAUDE.md` is generated by Laravel Boost from `.ai/guidelines/tamakkun.md`. After cloning, or after editing those guidelines, run:

```bash
php artisan boost:update
```

This regenerates `CLAUDE.md` and the local `.claude/skills` (which are never committed).

---

## Everyday commands

| Task | Command |
|---|---|
| Run tests | `vendor/bin/phpunit` |
| Format code (before every commit) | `vendor/bin/pint --dirty` |
| Check formatting like CI | `vendor/bin/pint --test` |
| Live log viewer | `php artisan pail` |
| Queue worker (local) | `php artisan queue:work` |
| Run the scheduler locally | `php artisan schedule:work` |
| List routes | `php artisan route:list --except-vendor` |
| Check this machine's setup | `php artisan tamakkun:doctor` |
| Create an admin account | `php artisan tamakkun:create-admin` |

### Queue

Emails and notifications are queued (`QUEUE_CONNECTION=database`). Locally, run `php artisan queue:work` in a separate terminal when testing anything that sends mail. In production the worker runs as a systemd service; see [docs/deployment.md](docs/deployment.md).

### Scheduler

Scheduled tasks are defined in `routes/console.php`: nightly backups, the daily alert refresh, morning reminders and scheduled announcements (`tamakkun:refresh-alerts`, `tamakkun:send-reminders`, `tamakkun:dispatch-announcements`). In production, cron runs `php artisan schedule:run` every minute. Locally, use `php artisan schedule:work`.

---

## Deployment

Production runs on a Rocky Linux VPS with CyberPanel, OpenLiteSpeed, `lsphp83` and MariaDB. The full procedure, including first install, web root, permissions, systemd queue worker, cron, SSH over port 443 and the update steps, is in **[docs/deployment.md](docs/deployment.md)**.

The short version of an update:

```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm ci && npm run build
php artisan optimize
sudo systemctl restart tamakkun-queue
```

---

## Git workflow (private repository)

The repository is **private**.

| Branch | Purpose |
|---|---|
| `develop` | Daily work. Every finished change is committed here. |
| `main` | What the live server runs. Always a fast-forward of `develop`. |

For each change:

1. Work on `develop`.
2. Run `vendor/bin/phpunit`; it must pass.
3. Run `vendor/bin/pint --dirty`; it must be clean.
4. Update `docs/` and `CHANGELOG.md`.
5. Commit with a `feat:`, `fix:` or `chore:` message, then push `develop`.
6. Fast-forward `main`: `git checkout main && git merge --ff-only develop && git push origin main`.

Never commit `.env`, `vendor/`, `node_modules/`, `public/build/`, backups, database dumps or `.claude/`. More detail is in [docs/workflow.md](docs/workflow.md).

---

## Documentation

| Topic | File |
|---|---|
| Architecture, design system, conventions | [docs/architecture.md](docs/architecture.md) |
| Windows / XAMPP setup | [docs/windows-setup.md](docs/windows-setup.md) |
| Database tables | [docs/database-schema.md](docs/database-schema.md) |
| Roles and permissions | [docs/roles-permissions.md](docs/roles-permissions.md) |
| Development workflow | [docs/workflow.md](docs/workflow.md) |
| Deployment | [docs/deployment.md](docs/deployment.md) |
| Security | [docs/security.md](docs/security.md) |
| Backups | [docs/backups.md](docs/backups.md) |
| Email | [docs/email.md](docs/email.md) |
| Testing | [docs/testing.md](docs/testing.md) |
| Student CSV import | [docs/imports.md](docs/imports.md) |
| Content sources and copyright | [docs/content-sources.md](docs/content-sources.md) |
| Progress formulas | [docs/progress.md](docs/progress.md) |
| Exam tracking | [docs/exams.md](docs/exams.md) |
| Counselor follow-up and alerts | [docs/alerts.md](docs/alerts.md) |
| Challenge, motivation, announcements, notifications | [docs/engagement.md](docs/engagement.md) |
| Reports (PDF/CSV) | [docs/reports.md](docs/reports.md) |
| Quizzes | [docs/quizzes.md](docs/quizzes.md) |
| Site graphics (sizes, palette, prompts) | [docs/graphics.md](docs/graphics.md) |
| Decisions and roadmap | [docs/decisions.md](docs/decisions.md) |

## Development with Claude Code cloud sessions

This project is also developed with [Claude Code](https://claude.ai/code) cloud sessions. Each session runs in an isolated cloud environment: it clones this repository, works on a branch, runs the tests and Pint, and pushes its commits back to GitHub for review.

## License

Private and proprietary. All rights reserved.
