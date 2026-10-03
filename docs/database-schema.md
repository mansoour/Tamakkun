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
| must_change_password | boolean, default false | Set when an admin sets the password. **Not mass assignable** |
| last_login_at | timestamp, nullable | Set by `RecordLastLogin` |
| email_verified_at | timestamp, nullable | |
| remember_token | string, nullable | |
| created_at / updated_at | timestamps | |

Role-specific data lives in `student_profiles` and `counselor_profiles`, not in `users`.

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

## School structure

```text
schools → academic_years → grades → classrooms → student_profiles
```

All parent foreign keys use `restrictOnDelete`, so a level that still has children cannot be deleted. `SchoolStructureService` also blocks the deletion with an Arabic message.

### schools

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| name | string, unique | |
| city | string(100), nullable | |
| is_active | boolean, default true | |
| timestamps | | |

### academic_years

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| school_id | FK schools (restrict) | |
| name | string(50) | for example `1447–1448`. Unique per school |
| starts_on / ends_on | date, nullable | `ends_on` must be after `starts_on` |
| is_current | boolean, indexed | Only one per school (enforced by `SchoolStructureService`) |
| timestamps | | |

### grades

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| academic_year_id | FK academic_years (restrict) | |
| name | string(100) | for example `الثالث الثانوي`. Unique per year |
| level | tinyint, nullable, indexed | 1–12. 12 is Grade 12 |
| sort_order | smallint | |
| timestamps | | |

### classrooms

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| grade_id | FK grades (restrict) | |
| name | string(100) | for example `3/1`. Unique per grade |
| sort_order | smallint | |
| timestamps | | |

## Profiles

### student_profiles

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | Route key for `/admin/students/{student}` and `/counselor/students/{student}` |
| user_id | FK users, unique, cascade | |
| school_id | FK schools (restrict) | |
| classroom_id | FK classrooms, nullable (restrict) | Must belong to `school_id` |
| counselor_id | FK users, nullable, null on delete | The assigned counselor. Must be a counselor of the same school |
| student_code | string(32), unique | School student code. Never the national ID |
| timestamps | | |

### counselor_profiles

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| user_id | FK users, unique, cascade | |
| school_id | FK schools (restrict) | |
| job_title | string(100), nullable | |
| phone | string(32), nullable | |
| timestamps | | |

## student_imports

| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| uploaded_by | FK users, nullable | |
| original_filename | string | |
| status | string(20), indexed | `App\Enums\ImportStatus`: previewed, queued, completed, failed |
| total_rows / imported_rows | unsigned int | |
| payload | longText, nullable | **Encrypted** validated rows. Cleared after import |
| row_errors | json, nullable | `[{row, messages[]}]` |
| error_message | text, nullable | |
| completed_at | timestamp, nullable | |
| timestamps | | |

## Learning content (v0.3)

```text
sources ─┐
categories (quantitative | verbal) ─┤
subjects → chapters → topics ───────┴→ contents
important_links (standalone)
```

### sources
`id, name, slug (unique), website_url (nullable, only after verification), logo_path, description, is_active, timestamps`

### categories
`id, section (quantitative|verbal), name, slug, description, sort_order, is_active, timestamps`. Unique on `(section, slug)`.

### subjects / chapters / topics
`subjects`: `id, name, slug (unique), sort_order, is_active`. `chapters`: `id, subject_id (restrict), name, sort_order`, unique per subject. `topics`: `id, chapter_id (restrict), name, sort_order`, unique per chapter.

### contents

| Column | Notes |
|---|---|
| title, slug (unique, Arabic allowed) | slug is generated from the title, with a numeric suffix if taken |
| description, body | body is plain text, shown escaped with line breaks |
| content_type | `ContentType`: video, lesson, link, article, practice, quiz |
| section | `ContentSection`: quantitative, verbal, tahsili |
| category_id | quantitative and verbal only (restrict) |
| subject_id, chapter_id, topic_id | tahsili only (restrict). The chapter must belong to the subject and the topic to the chapter |
| source_id | nullable, null on delete |
| stage, difficulty | `ContentStage` / `ContentDifficulty`, nullable |
| video_url | whitelisted YouTube or Vimeo URL (see `App\Support\VideoEmbed`) |
| external_url | https only |
| thumbnail_path | WebP on the `public` disk (`content-thumbnails/`) |
| duration_seconds, sort_order | |
| is_published, published_at, archived_at | visible = published, not archived, and `published_at` is null or in the past |
| created_by | FK users, null on delete |

Indexes: `(section, is_published, sort_order)`, `title`, `content_type`, `stage`, `archived_at`.

### important_links
`id, title, description, url (https), icon, category (LinkCategory: qiyas, qudurat, tahsili, official_services, learning_resources), is_official, sort_order, is_active, timestamps`
