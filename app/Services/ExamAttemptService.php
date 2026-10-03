<?php

namespace App\Services;

use App\Enums\ActivityEvent;
use App\Enums\ExamType;
use App\Models\ExamAttempt;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Creates, updates and deletes a student's exam attempts. Attempt numbers
 * are assigned automatically per exam type; score and date changes are
 * audited (brief §15) and logged as student activity (brief §69).
 */
class ExamAttemptService
{
    private const AUDITED = ['booking_status', 'exam_date', 'score', 'target_score'];

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly ActivityLogger $activity,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated StoreExamAttemptRequest data
     */
    public function create(User $student, array $data): ExamAttempt
    {
        return DB::transaction(function () use ($student, $data) {
            $type = ExamType::from($data['exam_type']);
            $next = (int) ExamAttempt::where('student_id', $student->id)->where('exam_type', $type)->lockForUpdate()->max('attempt_number') + 1;

            $attempt = ExamAttempt::create([...$this->clean($data), 'student_id' => $student->id, 'attempt_number' => $next]);

            $this->audit->record('exam.created', $attempt, null, $this->snapshot($attempt));
            $this->activity->log($student, ActivityEvent::EXAM_UPDATED, $attempt);

            return $attempt;
        });
    }

    /**
     * @param  array<string, mixed>  $data  validated UpdateExamAttemptRequest data (exam type is fixed)
     */
    public function update(ExamAttempt $attempt, array $data): ExamAttempt
    {
        return DB::transaction(function () use ($attempt, $data) {
            $before = $this->snapshot($attempt);
            $attempt->fill($this->clean(Arr::except($data, ['exam_type'])))->save();
            $after = $this->snapshot($attempt);

            $changed = array_keys(array_diff_assoc(array_map('strval', $after), array_map('strval', $before)));

            if ($changed !== []) {
                $this->audit->record('exam.updated', $attempt, Arr::only($before, $changed), Arr::only($after, $changed));
                $this->activity->log($attempt->student, ActivityEvent::EXAM_UPDATED, $attempt);
            }

            return $attempt;
        });
    }

    public function delete(ExamAttempt $attempt): void
    {
        $snapshot = $this->snapshot($attempt);
        $attempt->delete();
        $this->audit->record('exam.deleted', $attempt, $snapshot, null);
    }

    /**
     * A score only exists once the result is out; drop it otherwise.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function clean(array $data): array
    {
        if (($data['booking_status'] ?? null) !== 'result_received') {
            $data['score'] = null;
        }

        return Arr::only($data, ['exam_type', 'booking_status', 'exam_date', 'score', 'target_score', 'notes']);
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(ExamAttempt $attempt): array
    {
        return [
            'exam_type' => $attempt->exam_type->value,
            'attempt_number' => $attempt->attempt_number,
            'booking_status' => $attempt->booking_status->value,
            'exam_date' => $attempt->exam_date?->toDateString(),
            'score' => $attempt->score,
            'target_score' => $attempt->target_score,
        ];
    }
}
