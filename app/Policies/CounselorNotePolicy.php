<?php

namespace App\Policies;

use App\Models\CounselorNote;
use App\Models\User;

/**
 * Only the author may delete a note. Students never reach these routes;
 * they only ever see their own non-private notes on the dashboard.
 */
class CounselorNotePolicy
{
    public function delete(User $user, CounselorNote $note): bool
    {
        return $note->counselor_id === $user->id;
    }
}
