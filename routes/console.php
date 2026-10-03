<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
|
| Production runs `php artisan schedule:run` from cron every minute.
| Times are in the application timezone (Asia/Riyadh). See docs/backups.md.
|
*/

Schedule::command('backup:clean')->dailyAt('01:30')->onOneServer();
Schedule::command('backup:run')->dailyAt('02:00')->onOneServer();
Schedule::command('backup:monitor')->dailyAt('03:00')->onOneServer();
