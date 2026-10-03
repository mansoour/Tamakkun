<?php

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use App\Services\AnnouncementService;
use App\Services\EnvironmentDoctor;
use App\Services\ReminderService;
use App\Services\StudentAlertService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

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

Artisan::command('tamakkun:doctor', function (EnvironmentDoctor $doctor) {
    $results = $doctor->run();
    $marks = ['ok' => '<fg=green>✓ ok</>', 'warn' => '<fg=yellow>! warn</>', 'fail' => '<fg=red>✗ fail</>'];

    $this->table(['Check', 'Status', 'Detail'], array_map(fn (array $r) => [$r['check'], $marks[$r['status']], $r['detail']], $results));

    $failures = count(array_filter($results, fn (array $r) => $r['status'] === 'fail'));
    $failures === 0 ? $this->info('Everything needed to run Tamakkun is in place.') : $this->error("{$failures} problem(s) to fix.");

    return $failures === 0 ? 0 : 1;
})->purpose('Check PHP, extensions, database, storage and assets on this machine');

Artisan::command('tamakkun:create-admin', function () {
    $data = [
        'name' => $this->ask('Full name'),
        'username' => $this->ask('Username (used to log in)'),
        'email' => $this->ask('Email (optional, for password resets)') ?: null,
        'password' => $this->secret('Password (at least 10 characters)'),
    ];

    $validator = Validator::make($data, [
        'name' => ['required', 'string', 'max:255'],
        'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
        'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
        'password' => ['required', 'string', Password::min(10), 'max:72'],
    ]);

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        return 1;
    }

    $user = User::create($data);
    $user->forceFill(['status' => UserStatus::ACTIVE, 'must_change_password' => false])->save();
    $user->assignRole(RoleName::ADMIN->value);

    $this->info("Admin account \"{$user->username}\" created. Log in at ".route('login'));

    return 0;
})->purpose('Create an active admin account (use for the first admin on a new install)');

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
