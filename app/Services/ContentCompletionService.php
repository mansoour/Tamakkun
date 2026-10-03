<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Enums\ProgressStatus;
use App\Models\Content;
use App\Models\StudentContentProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The student's explicit progress actions: ابدأ (start), أنجزت (complete)
 * and undo. Opening a content page or an external URL never marks
 * anything as started or complete.
 */
class ContentCompletionService
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function progressFor(User $student, Content $content): ?StudentContentProgress
    {
        return StudentContentProgress::where('student_id', $student->id)->where('content_id', $content->id)->first();
    }

    public function start(User $student, Content $content): StudentContentProgress
    {
        $this->ensureVisible($content);

        return DB::transaction(function () use ($student, $content) {
            $progress = $this->progressFor($student, $content) ?? new StudentContentProgress([
                'student_id' => $student->id,
                'content_id' => $content->id,
                'status' => ProgressStatus::NOT_STARTED,
            ]);

            if ($progress->status === ProgressStatus::NOT_STARTED) {
                $progress->fill(['status' => ProgressStatus::IN_PROGRESS, 'started_at' => now(), 'progress_percentage' => 0]);
                $this->activity->log($student, ActivityEvent::CONTENT_STARTED, $content);
            }

            $progress->last_viewed_at = now();
            $progress->save();

            return $progress;
        });
    }

    public function complete(User $student, Content $content): StudentContentProgress
    {
        $this->ensureVisible($content);

        return DB::transaction(function () use ($student, $content) {
            $progress = $this->start($student, $content);

            if ($progress->status !== ProgressStatus::COMPLETED) {
                $progress->fill(['status' => ProgressStatus::COMPLETED, 'completed_at' => now(), 'progress_percentage' => 100])->save();
                $this->activity->log($student, ActivityEvent::CONTENT_COMPLETED, $content);
            }

            return $progress;
        });
    }

    /**
     * Undo an accidental "أنجزت": back to in progress.
     */
    public function uncomplete(User $student, Content $content): ?StudentContentProgress
    {
        $progress = $this->progressFor($student, $content);

        if ($progress?->status === ProgressStatus::COMPLETED) {
            $progress->fill(['status' => ProgressStatus::IN_PROGRESS, 'completed_at' => null, 'progress_percentage' => 0])->save();
            $this->activity->log($student, ActivityEvent::CONTENT_UNCOMPLETED, $content);
        }

        return $progress;
    }

    /**
     * Viewing updates the timestamp of existing progress only — it never creates or advances it.
     */
    public function touchViewed(User $student, Content $content): void
    {
        StudentContentProgress::where('student_id', $student->id)->where('content_id', $content->id)
            ->update(['last_viewed_at' => now()]);
    }

    private function ensureVisible(Content $content): void
    {
        if (! $content->isVisible()) {
            throw new InvalidArgumentException('Content is not available.');
        }
    }
}
