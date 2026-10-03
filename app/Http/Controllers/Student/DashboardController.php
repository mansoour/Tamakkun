<?php

namespace App\Http\Controllers\Student;

use App\Enums\ProgressStatus;
use App\Http\Controllers\Controller;
use App\Models\StudentContentProgress;
use App\Services\ExamProgressService;
use App\Services\StudentProgressService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, StudentProgressService $progress, ExamProgressService $exams): View
    {
        $student = $request->user();
        $examSummary = $exams->summary($student);

        return view('student.dashboard', [
            'summary' => $progress->summary($student),
            'exams' => $examSummary,
            'sharedNotes' => $student->counselorNotes()->where('is_private', false)->with('counselor')->latest()->limit(3)->get(),
            'nextExam' => $exams->nextExam($examSummary),
            'continue' => StudentContentProgress::where('student_id', $student->id)
                ->where('status', ProgressStatus::IN_PROGRESS)
                ->whereHas('content', fn ($q) => $q->visible())
                ->with('content.source')->latest('last_viewed_at')->first()?->content,
        ]);
    }
}
