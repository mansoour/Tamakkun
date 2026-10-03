# Backups

Tamakkun uses `spatie/laravel-backup` (config: `config/backup.php`).

## What is backed up

- **Database:** the default connection (`mysql`), dumped with `mysqldump`.
- **Uploaded files:** everything under `storage/app` (public and private disks).
- **Not** backed up: code (it lives in Git) and `.env` (kept by the server administrator in the password manager).

## Encryption

Archives are zip files encrypted with `BACKUP_ARCHIVE_PASSWORD` (`encryption: default`, which is AES-256). If the password is empty, backups are **not** encrypted, so production must set it. Store the password in the password manager: without it the archives cannot be restored.

## Schedule (`routes/console.php`, Asia/Riyadh)

| Time | Command |
|---|---|
| 01:30 | `backup:clean` |
| 02:00 | `backup:run` |
| 03:00 | `backup:monitor` |

This requires the cron entry from [deployment.md](deployment.md#scheduler-cron).

## Storage and retention

Backups are stored on the `local` disk under `storage/app/private/Tamakkun/`, which is never committed. Retention follows the package defaults:

- all backups for 7 days
- daily backups for 16 days
- weekly backups for 8 weeks
- monthly backups for 4 months
- yearly backups for 2 years
- oldest deleted first above 5000 MB

> **Recommended before go-live:** add an off-server disk (for example S3-compatible storage) to `backup.destination.disks`. A backup on the same server does not survive a server loss.

## Notifications

Failure and unhealthy-backup notifications are emailed to `BACKUP_NOTIFICATION_EMAIL`. Success notifications are switched off to avoid noise.

## Restore procedure

1. Download the latest archive from `storage/app/private/Tamakkun/` (or the off-server disk).
2. Unzip it with the archive password: `7z x <file>.zip`. Plain `unzip` may not support AES.
3. **Database:** `mysql -u <user> -p <database> < db-dumps/mysql-<database>.sql`. Restore into a fresh database first if you are verifying.
4. **Files:** copy the extracted `storage/app/...` back into `/home/<domain>/app/storage/app/`, then fix ownership (see [deployment.md](deployment.md#permissions)).
5. `php artisan optimize:clear && php artisan optimize`, then restart the queue worker.

## Verification

- **Verified in v0.8:** `backup:run --only-db` produced an AES-encrypted archive (plain `unzip` refuses it, as expected) and `backup:list` reported it healthy. Production needs `mysqldump` (part of MariaDB) on the PATH.

- Check `php artisan backup:list` weekly. The newest backup must be under 24 hours old.
- **Once a month**, do a test restore into a separate, temporary database and check that row counts for `users` and later core tables look right. Record the date in the ops log.
- A backup is not considered working until a restore has been tested.
