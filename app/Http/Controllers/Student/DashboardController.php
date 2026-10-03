<?php

namespace App\Http\Controllers\Student;

use App\Enums\ProgressStatus;
use App\Http\Controllers\Controller;
use App\Models\StudentContentProgress;
use App\Services\StudentProgressService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, StudentProgressService $progress): View
    {
        $student = $request->user();

        return view('student.dashboard', [
            'summary' => $progress->summary($student),
            'continue' => StudentContentProgress::where('student_id', $student->id)
                ->where('status', ProgressStatus::IN_PROGRESS)
                ->whereHas('content', fn ($q) => $q->visible())
                ->with('content.source')->latest('last_viewed_at')->first()?->content,
        ]);
    }
}
