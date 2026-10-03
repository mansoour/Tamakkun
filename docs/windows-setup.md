# Local setup on Windows (XAMPP for the database only)

This guide sets up Tamakkun on a Windows PC for development or a local pilot. XAMPP provides **MariaDB only**. PHP is a separate PHP 8.3 install at `C:\php`, and the site is served by `php artisan serve`, not by XAMPP Apache.

When you finish, `php artisan tamakkun:doctor` should report no problems.

## 1. Install the tools

| Tool | Where | Notes |
|---|---|---|
| PHP 8.3 (x64, Thread Safe zip) | https://windows.php.net/download/ | Extract to `C:\php` |
| Composer | https://getcomposer.org/download/ | When the installer asks for PHP, choose `C:\php\php.exe` |
| Node.js 22 LTS | https://nodejs.org/ | Includes npm |
| XAMPP | https://www.apachefriends.org/ | Use only the MySQL (MariaDB) module |
| Git | https://git-scm.com/ | |

Do **not** use the older PHP that ships inside XAMPP (`C:\xampp\php`). Add `C:\php` to the Windows `Path` (System Properties → Environment Variables) **above** any XAMPP entry. Otherwise, call `C:\php\php.exe` directly everywhere this guide says `php`.

Check in a new terminal:

```bat
php -v
where php
```

`php -v` must print 8.3.x, and the first line of `where php` must be `C:\php\php.exe`.

## 2. Configure PHP (`C:\php\php.ini`)

1. Copy `C:\php\php.ini-development` to `C:\php\php.ini`.
2. Remove the leading `;` from this line:

   ```ini
   extension_dir = "ext"
   ```

3. Enable the extensions Tamakkun needs. Remove the leading `;` from each of these lines:

   ```ini
   extension=curl
   extension=fileinfo
   extension=gd
   extension=intl
   extension=mbstring
   extension=openssl
   extension=pdo_mysql
   extension=pdo_sqlite
   extension=zip
   ```

   `bcmath`, `ctype`, `tokenizer` and `xml` are built into the Windows build. If `php -m` does not list one of them, look for a matching `extension=` line and enable it.

4. Optional, but helpful for large imports:

   ```ini
   memory_limit = 512M
   upload_max_filesize = 10M
   post_max_size = 12M
   ```

Verify:

```bat
php -m
```

The list must include: bcmath, ctype, curl, fileinfo, gd, intl, mbstring, openssl, pdo_mysql, pdo_sqlite, tokenizer, xml, zip.

## 3. Create the database

1. Open the XAMPP Control Panel and start **MySQL** only.
2. Open phpMyAdmin (or the MySQL shell) and run:

   ```sql
   CREATE DATABASE tamakkun CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

## 4. Get the code and install

```bat
git clone git@github.com:mansoour/Tamakkun.git
cd Tamakkun
git checkout develop

composer install
copy .env.example .env
php artisan key:generate
```

`.env.example` already points at XAMPP's MariaDB (`root` with no password, database `tamakkun`). Edit `.env` if yours is different.

```bat
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
```

- `--seed` adds **fake** demo accounts for local testing only. Skip it for a real pilot and create the first admin account by hand (see [deployment.md](deployment.md)).
- On Windows, `storage:link` may need a terminal opened with **Run as administrator**.

## 5. Check and run

```bat
php artisan tamakkun:doctor
php artisan serve
```

Open http://localhost:8000. To test anything that sends email or notifications, also run in a second terminal:

```bat
php artisan queue:work
```

To run the daily jobs (alerts, reminders, backups) on the PC, run in a third terminal:

```bat
php artisan schedule:work
```

While you are changing the design, use `npm run dev` instead of `npm run build` so the page reloads automatically.

## 6. Updating later

```bat
git pull
composer install
php artisan migrate
npm install
npm run build
php artisan optimize:clear
php artisan tamakkun:doctor
```

## Troubleshooting

| Message | Fix |
|---|---|
| `could not find driver` | `extension=pdo_mysql` is not enabled in `C:\php\php.ini`, or a different `php.exe` is running (`where php`). |
| `SQLSTATE[HY000] [2002] No connection could be made` | MySQL is not started in XAMPP, or the port in `.env` is not 3306. |
| `Unknown database 'tamakkun'` | Create the database (step 3). |
| `The bcmath extension is required` / `intl` / `zip` | Enable the extension in `php.ini`, then open a new terminal. |
| `Vite manifest not found` | Run `npm run build` (or keep `npm run dev` running). |
| Uploaded images do not show | Run `php artisan storage:link` (as administrator). |
| Arabic text looks wrong in MariaDB | The database must use `utf8mb4` (step 3). |
| Composer uses the wrong PHP | Re-run the Composer installer and choose `C:\php\php.exe`. |

`php artisan tamakkun:doctor` checks all of the above and names the fix for each problem it finds.
