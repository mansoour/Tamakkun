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
```

## Current coverage (v0.1)

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
| `Auth/*` (Breeze) | Email verification, password confirmation, reset and update |
