<?php

namespace App\Http\Controllers\Counselor;

use App\Http\Controllers\Controller;
use App\Models\StudentProfile;
use App\Services\StudentRosterService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Exam-focused views of the roster: upcoming exams and results.
 */
class ExamOverviewController extends Controller
{
    public function exams(Request $request, StudentRosterService $roster): View
    {
        $this->authorize('viewAny', StudentProfile::class);
        $rows = $roster->rows($request->user());

        return view('counselor.exams', [
            'upcoming' => $rows->filter(fn ($r) => $r['next_exam'])->sortBy(fn ($r) => $r['next_exam']['days'])->values(),
            'notBooked' => $rows->where('booked_any', false)->values(),
        ]);
    }

    public function results(Request $request, StudentRosterService $roster): View
    {
        $this->authorize('viewAny', StudentProfile::class);

        return view('counselor.results', [
            'rows' => $roster->rows($request->user())
                ->filter(fn ($r) => $r['qudurat']['latest'] !== null || $r['tahsili']['latest'] !== null)
                ->sortByDesc(fn ($r) => $r['qudurat']['improvement'] ?? -1000)->values(),
        ]);
    }
}
