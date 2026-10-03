<?php

namespace App\Services;

use App\Enums\FollowUpStatus;
use App\Models\CounselorNote;
use App\Models\StudentProfile;
use App\Models\User;

/**
 * Counselor follow-up actions: manual follow-up status and notes. Audited
 * without storing note text in the audit log (notes may be sensitive).
 */
class FollowUpService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function setStatus(StudentProfile $student, FollowUpStatus $status): void
    {
        $old = $student->follow_up_status;

        if ($old === $status) {
            return;
        }

        $student->forceFill(['follow_up_status' => $status, 'follow_up_updated_at' => now()])->save();
        $this->audit->record('student.follow_up_changed', $student, ['follow_up_status' => $old?->value], ['follow_up_status' => $status->value]);
    }

    public function addNote(StudentProfile $student, User $counselor, string $note, bool $isPrivate): CounselorNote
    {
        $created = CounselorNote::create([
            'student_id' => $student->user_id,
            'counselor_id' => $counselor->id,
            'note' => $note,
            'is_private' => $isPrivate,
        ]);

        $this->audit->record('note.created', $created, null, ['student_id' => $student->user_id, 'is_private' => $isPrivate]);

        return $created;
    }

    public function deleteNote(CounselorNote $note): void
    {
        $note->delete();
        $this->audit->record('note.deleted', $note, ['student_id' => $note->student_id, 'is_private' => $note->is_private], null);
    }
}
