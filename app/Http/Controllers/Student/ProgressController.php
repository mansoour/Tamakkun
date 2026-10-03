<?php

namespace App\Http\Controllers\Student;

use App\Enums\ProgressStatus;
use App\Http\Controllers\Controller;
use App\Models\StudentContentProgress;
use App\Services\StudentProgressService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgressController extends Controller
{
    public function __invoke(Request $request, StudentProgressService $progress): View
    {
        $student = $request->user();
        $recent = fn (ProgressStatus $status, string $order) => StudentContentProgress::where('student_id', $student->id)
            ->where('status', $status)
            ->whereHas('content', fn ($q) => $q->visible())
            ->with('content.source')
            ->latest($order)->limit(6)->get()->pluck('content');

        return view('student.progress', [
            'summary' => $progress->summary($student),
            'inProgress' => $recent(ProgressStatus::IN_PROGRESS, 'last_viewed_at'),
            'completed' => $recent(ProgressStatus::COMPLETED, 'completed_at'),
        ]);
    }
}
