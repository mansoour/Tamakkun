<?php

namespace App\Services;

use App\Enums\ExamBookingStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\ExamAttempt;
use App\Models\User;
use App\Notifications\ExamReminder;

/**
 * Morning reminders (scheduled `tamakkun:send-reminders`): today's challenge
 * and booked exams 7 days and 1 day away. Each reminder is sent once.
 */
class ReminderService
{
    public const EXAM_REMINDER_DAYS = [7, 1];

    public function __construct(private readonly DailyChallengeService $challenges) {}

    /**
     * @return array{challenge: bool, exam_reminders: int}
     */
    public function sendMorningReminders(): array
    {
        $students = User::role(RoleName::STUDENT->value)->where('status', UserStatus::ACTIVE)->get();

        return [
            'challenge' => $this->challenges->notifyToday($students),
            'exam_reminders' => $this->sendExamReminders(),
        ];
    }

    public function sendExamReminders(): int
    {
        $sent = 0;

        foreach (self::EXAM_REMINDER_DAYS as $days) {
            ExamAttempt::with('student')
                ->where('booking_status', ExamBookingStatus::BOOKED)
                ->whereDate('exam_date', today()->addDays($days))
                ->get()
                ->each(function (ExamAttempt $attempt) use ($days, &$sent) {
                    $student = $attempt->student;
                    $notification = new ExamReminder($attempt, $days);

                    if ($student->status !== UserStatus::ACTIVE
                        || $student->notifications()->where('data->key', $notification->key())->exists()) {
                        return;
                    }

                    $student->notify($notification);
                    $sent++;
                });
        }

        return $sent;
    }
}
