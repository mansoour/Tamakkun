<?php

use App\Services\StudentAlertService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('tamakkun:refresh-alerts', function (StudentAlertService $alerts) {
    $created = $alerts->refreshAll();
    $this->info("Alerts refreshed ({$created} new).");
})->purpose('Generate and auto-resolve automatic student follow-up alerts');

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
Schedule::command('tamakkun:refresh-alerts')->dailyAt('05:30')->onOneServer();
