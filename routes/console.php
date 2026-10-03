<?php

use App\Services\AnnouncementService;
use App\Services\ReminderService;
use App\Services\StudentAlertService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('tamakkun:refresh-alerts', function (StudentAlertService $alerts) {
    $created = $alerts->refreshAll();
    $this->info("Alerts refreshed ({$created} new).");
})->purpose('Generate and auto-resolve automatic student follow-up alerts');

Artisan::command('tamakkun:send-reminders', function (ReminderService $reminders) {
    $result = $reminders->sendMorningReminders();
    $this->info('Challenge notified: '.($result['challenge'] ? 'yes' : 'no')."; exam reminders: {$result['exam_reminders']}.");
})->purpose("Notify students about today's challenge and upcoming exams");

Artisan::command('tamakkun:dispatch-announcements', function (AnnouncementService $announcements) {
    $this->info("Announcements dispatched: {$announcements->dispatchDue()}.");
})->purpose('Notify recipients of scheduled announcements whose start time has arrived');

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
Schedule::command('tamakkun:send-reminders')->dailyAt('07:00')->onOneServer();
Schedule::command('tamakkun:dispatch-announcements')->everyFiveMinutes()->onOneServer();
