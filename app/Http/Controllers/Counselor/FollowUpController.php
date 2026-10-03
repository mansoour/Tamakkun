<?php

namespace App\Http\Controllers\Counselor;

use App\Enums\FollowUpStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Counselor\StoreCounselorNoteRequest;
use App\Http\Requests\Counselor\UpdateFollowUpStatusRequest;
use App\Models\CounselorNote;
use App\Models\StudentProfile;
use App\Services\FollowUpService;
use App\Services\StudentRosterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FollowUpController extends Controller
{
    public function __construct(private readonly FollowUpService $followUp) {}

    /**
     * تحتاج متابعة: manual follow-up statuses plus students with concerning alerts.
     */
    public function index(Request $request, StudentRosterService $roster): View
    {
        $this->authorize('viewAny', StudentProfile::class);

        return view('counselor.follow-up', [
            'rows' => $roster->rows($request->user())
                ->filter(fn ($r) => $r['needs_follow_up'] || in_array($r['follow_up'], FollowUpStatus::active(), true))
                ->sortByDesc('open_alerts')->values(),
        ]);
    }

    public function updateStatus(UpdateFollowUpStatusRequest $request, StudentProfile $student): RedirectResponse
    {
        $this->followUp->setStatus($student, FollowUpStatus::from($request->validated('follow_up_status')));

        return back()->with('success', 'تم تحديث حالة المتابعة.');
    }

    public function storeNote(StoreCounselorNoteRequest $request, StudentProfile $student): RedirectResponse
    {
        $this->followUp->addNote($student, $request->user(), $request->validated('note'), $request->validated('is_private'));

        return back()->with('success', 'تمت إضافة الملاحظة.');
    }

    public function destroyNote(StudentProfile $student, CounselorNote $note): RedirectResponse
    {
        abort_unless($note->student_id === $student->user_id, 404);
        $this->authorize('delete', $note);
        $this->followUp->deleteNote($note);

        return back()->with('success', 'تم حذف الملاحظة.');
    }
}
