<?php

namespace App\Http\Controllers\Counselor;

use App\Enums\ExamType;
use App\Enums\FollowUpStatus;
use App\Enums\ProgressStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Classroom;
use App\Models\StudentContentProgress;
use App\Models\StudentProfile;
use App\Services\ExamProgressService;
use App\Services\StudentProgressService;
use App\Services\StudentRosterService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

/**
 * The counselor's students: filterable roster and the tabbed student page.
 */
class StudentController extends Controller
{
    public function index(Request $request, StudentRosterService $roster): View
    {
        $this->authorize('viewAny', StudentProfile::class);

        $rows = $roster->filter($roster->rows($request->user()), $request->only([
            'q', 'classroom_id', 'booking', 'exam_type', 'activity', 'completion', 'follow_up', 'score_below',
        ]));

        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 25;

        return view('counselor.students.index', [
            'students' => new LengthAwarePaginator($rows->forPage($page, $perPage)->values(), $rows->count(), $perPage, $page, [
                'path' => $request->url(), 'query' => $request->query(),
            ]),
            'classrooms' => Classroom::with('grade')
                ->whereIn('id', $roster->scopeFor($request->user())->whereNotNull('classroom_id')->pluck('classroom_id'))
                ->get()->mapWithKeys(fn (Classroom $c) => [$c->id => $c->label()]),
            'followUpStatuses' => FollowUpStatus::cases(),
            'examTypes' => ExamType::cases(),
        ]);
    }

    public function show(Request $request, StudentProfile $student, StudentProgressService $progress, ExamProgressService $exams): View
    {
        $this->authorize('view', $student);

        $user = $student->user;
        $contentByStatus = fn (ProgressStatus $status, string $order) => StudentContentProgress::where('student_id', $user->id)
            ->where('status', $status)->with('content')->latest($order)->limit(10)->get();

        $examSummary = $exams->summary($user);

        return view('counselor.students.show', [
            'next' => $exams->nextExam($examSummary),
            'student' => $student->load(['user', 'school', 'classroom.grade.academicYear']),
            'summary' => $progress->summary($user),
            'exams' => $examSummary,
            'activities' => ActivityLog::where('user_id', $user->id)->with('subject')->latest('id')->limit(30)->get(),
            'completed' => $contentByStatus(ProgressStatus::COMPLETED, 'completed_at'),
            'inProgress' => $contentByStatus(ProgressStatus::IN_PROGRESS, 'last_viewed_at'),
            'notes' => $user->counselorNotes()->with('counselor')->latest()->get(),
            'alerts' => $user->alerts()->latest('generated_at')->get(),
            'canFollowUp' => $request->user()->can('followUp', $student),
            'followUpStatuses' => FollowUpStatus::cases(),
        ]);
    }
}
