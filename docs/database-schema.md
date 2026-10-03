# Database schema

MariaDB 10.3+ in production and 10.4 locally (XAMPP). Tests use SQLite in memory. Charset `utf8mb4`.

This file lists the tables that exist **now**. Tables planned in the brief (schools, content, exams and so on) are added as their phase is built.

## users

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| name | string | |
| username | string(64), unique | Student code for students. Used to log in |
| email | string, nullable, unique | Optional for students. Used by counselors and admins |
| password | string | bcrypt hash |
| status | string(20), indexed, default `pending` | `App\Enums\UserStatus`: `pending`, `active`, `suspended`, `disabled`. Only `active` can sign in. **Not mass assignable** |
| last_login_at | timestamp, nullable | Set by `RecordLastLogin` |
| email_verified_at | timestamp, nullable | |
| remember_token | string, nullable | |
| created_at / updated_at | timestamps | |

Role-specific data will live in `student_profiles` and `counselor_profiles` (Phase 1), not in `users`.

## Framework tables

`password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`.

## Permission tables (spatie/laravel-permission)

`permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions`. Initial rows are created by the migration `2026_10_03_000002_seed_initial_roles_and_permissions` (see [roles-permissions.md](roles-permissions.md)).

## settings

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| key | string(100), unique | Must exist in `config/tamakkun.php` → `settings` |
| value | text, nullable | JSON-encoded so types survive |
| created_at / updated_at | timestamps | |

## audit_logs

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| user_id | FK users, nullable, null on delete | Who did it |
| action | string(100), indexed | for example `settings.updated` |
| auditable_type / auditable_id | nullable morph, indexed | Affected record |
| old_values / new_values | json, nullable | |
| ip_address | string(45), nullable | |
| user_agent | text, nullable | |
| created_at | timestamp, indexed | Append-only, no `updated_at` |

## email_logs

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| user_id | FK users, nullable, null on delete | Matched by recipient email |
| recipient | string | |
| subject | string, nullable | |
| template | string, nullable | Mailable or Notification class |
| status | string(20), indexed | `App\Enums\EmailStatus`: `sent`, `failed` |
| provider_message_id | string, nullable | |
| error_message | text, nullable | |
| sent_at | timestamp, nullable | |
| created_at | timestamp, indexed | |
