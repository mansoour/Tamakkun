# Deployment

> **Live at https://tamakkun.mror.top** (deployed 2026-10-04), behind Cloudflare (SSL/TLS: Full (strict)). The procedure below is what was used; see «Notes from the first deployment».

## Server

- Rocky Linux VPS with CyberPanel, OpenLiteSpeed, `lsphp83` and MariaDB 10.3.
- The website, database and SSL certificate are created in CyberPanel.
- The PHP CLI on the server is `/usr/local/lsws/lsphp83/bin/php`. The commands below write `php`. Use the full path, or alias it, if `php` is not lsphp83.
- Required PHP extensions include `bcmath`, `intl`, `gd` (WebP support for image optimisation), `zip`, `mbstring` and `pdo_mysql`. mPDF (PDF reports) needs `mbstring` and `gd`.
- `mysqldump` must be on the PATH for backups.

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

Create the first admin account (it asks for the name, username, optional email and a password of at least 10 characters). Do **not** run the demo seeder in production; it refuses to run there anyway.

```bash
php artisan tamakkun:create-admin
php artisan tamakkun:doctor     # checks PHP, extensions, database, storage and assets
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

## Notes from the first deployment

- **Layout used:** the project lives in the CyberPanel child-domain folder (`/home/<domain>/<subdomain-folder>`), cloned there directly (CyberPanel's placeholder `index.html` removed first). The vHost `docRoot` was changed to `…/public` with `sed` on `/usr/local/lsws/conf/vhosts/<subdomain>/vhost.conf`, then `lswsctrl restart`.
- **Repository is public**, so the server clones over HTTPS (`https://github.com/mansoour/Tamakkun.git`) and no deploy key is needed. If the repo is made private, use the deploy-key setup above.
- **Run PHP, Composer and artisan as the site user** (`sudo -u <site-user> …`), so files in `storage/` and `bootstrap/cache/` stay writable by the PHP process.
- **Node.js:** the server's Node 20 is installed with nvm under `/root/.nvm`, which the site user cannot read (it falls back to an old Node 18 that cannot run Vite 8). Build the assets **as root** and hand them back:

  ```bash
  npm ci && npm run build && chown -R <site-user>:<site-user> public/build node_modules
  ```

  If the build reports «Cannot find native binding», delete `node_modules` and run `npm ci` again with Node 20.
- **Cloudflare:** `bootstrap/app.php` trusts only Cloudflare's IP ranges, so rate limits and logs see the visitor's real IP. Keep this list in sync with https://www.cloudflare.com/ips/.
- **Mail:** `MAIL_MAILER=log` until a Resend API key is added (then `MAIL_MAILER=resend` and `RESEND_API_KEY=…` in `.env`, followed by `php artisan optimize`).

### Update routine on this server

```bash
PHP=/usr/local/lsws/lsphp83/bin/php
cd /home/<domain>/<subdomain-folder>
sudo -u <site-user> $PHP artisan down --retry=60
sudo -u <site-user> git pull origin main
sudo -u <site-user> $PHP $(which composer) install --no-dev --optimize-autoloader --no-interaction
sudo -u <site-user> $PHP artisan migrate --force
npm ci && npm run build && chown -R <site-user>:<site-user> public/build node_modules
sudo -u <site-user> $PHP artisan optimize
systemctl restart tamakkun-queue
sudo -u <site-user> $PHP artisan up
```
