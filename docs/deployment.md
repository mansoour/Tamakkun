# Deployment

> Production deployment has **not** been performed yet. This is the agreed procedure.

## Server

- Rocky Linux VPS with CyberPanel, OpenLiteSpeed, `lsphp83` and MariaDB 10.3.
- The website, database and SSL certificate are created in CyberPanel.
- The PHP CLI on the server is `/usr/local/lsws/lsphp83/bin/php`. The commands below write `php`. Use the full path, or alias it, if `php` is not lsphp83.
- Required PHP extensions include `bcmath`, `intl`, `gd`, `zip`, `mbstring` and `pdo_mysql`.

## File layout

```text
/home/<domain>/app            ← Laravel project (git clone)
/home/<domain>/app/public     ← web root (set in CyberPanel → vHost Conf: docRoot)
```

- **Never** point the web root at the project root.
- All files are owned by the website's own system user (the one CyberPanel created for `<domain>`), not root.

## GitHub access (read-only deploy key over port 443)

Outgoing port 22 is blocked, so GitHub SSH goes through `ssh.github.com:443`.

```bash
# as the site user
ssh-keygen -t ed25519 -C "tamakkun-deploy" -f ~/.ssh/tamakkun_deploy -N ""
cat ~/.ssh/tamakkun_deploy.pub   # add in GitHub → repo → Settings → Deploy keys (read-only)
```

`~/.ssh/config`:

```text
Host github.com
    HostName ssh.github.com
    Port 443
    User git
    IdentityFile ~/.ssh/tamakkun_deploy
    IdentitiesOnly yes
```

Test with `ssh -T git@github.com`.

## First install

```bash
cd /home/<domain>
git clone --branch main git@github.com:mansoour/Tamakkun.git app
cd app
composer install --no-dev --optimize-autoloader
cp .env.example .env && php artisan key:generate
# edit .env (values below)
php artisan migrate --force
npm ci && npm run build
php artisan storage:link        # required: content thumbnails are served from /storage
php artisan optimize
```

Create the first admin account with `php artisan tinker`. Do **not** run the demo seeder in production; it refuses to run there anyway.

```php
$u = App\Models\User::create(['name' => '…', 'username' => '…', 'email' => '…', 'password' => '…']);
$u->forceFill(['status' => App\Enums\UserStatus::ACTIVE])->save();
$u->assignRole('admin');
```

## Production `.env`

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<domain>
APP_TIMEZONE=Asia/Riyadh

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=warning
LOG_DAILY_DAYS=14

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=<cyberpanel db>
DB_USERNAME=<cyberpanel user>
DB_PASSWORD=<secret>

SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database

MAIL_MAILER=resend
MAIL_FROM_ADDRESS=no-reply@<domain>
RESEND_API_KEY=<secret>

BACKUP_ARCHIVE_PASSWORD=<long random secret, stored in the password manager>
BACKUP_NOTIFICATION_EMAIL=<admin email>
```

## Permissions

```bash
chown -R <site-user>:<site-user> /home/<domain>/app
find /home/<domain>/app -type d -exec chmod 755 {} \;
find /home/<domain>/app -type f -exec chmod 644 {} \;
chmod -R 775 /home/<domain>/app/storage /home/<domain>/app/bootstrap/cache
chmod 600 /home/<domain>/app/.env
```

## Queue worker (systemd)

`/etc/systemd/system/tamakkun-queue.service`:

```ini
[Unit]
Description=Tamakkun queue worker
After=network.target mariadb.service

[Service]
User=<site-user>
Group=<site-user>
Restart=always
RestartSec=5
WorkingDirectory=/home/<domain>/app
ExecStart=/usr/local/lsws/lsphp83/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now tamakkun-queue
```

## Scheduler (cron)

In the site user's crontab (`crontab -e`, or CyberPanel → Cron Jobs):

```cron
* * * * * cd /home/<domain>/app && /usr/local/lsws/lsphp83/bin/php artisan schedule:run >> /dev/null 2>&1
```

The scheduler runs nightly backups, the **daily alert refresh** (`tamakkun:refresh-alerts`, 05:30), **morning reminders** (`tamakkun:send-reminders`, 07:00) and **scheduled announcements** (`tamakkun:dispatch-announcements`, every 5 minutes). Notifications are queued, so the queue worker must be running. After restoring data or a large import, you can run `php artisan tamakkun:refresh-alerts` manually.

## Update procedure (every release)

```bash
cd /home/<domain>/app
php artisan down --retry=60
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm ci
npm run build
php artisan optimize          # caches config, events, routes and views
# chown/chmod if any files were created as another user (see Permissions)
sudo systemctl restart tamakkun-queue
php artisan up
```

**Chosen caching approach:** `php artisan optimize` (Laravel 13 caches config, events, routes and views). Use it consistently. Clear with `php artisan optimize:clear`. Always restart the queue worker after deploying code.
