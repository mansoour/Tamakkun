<?php

namespace App\Http\Controllers\Counselor;

use App\Http\Controllers\Controller;
use App\Models\StudentAlert;
use App\Services\StudentRosterService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, StudentRosterService $roster): View
    {
        $rows = $roster->rows($request->user());

        return view('counselor.dashboard', [
            'kpis' => $roster->kpis($rows),
            'attention' => $rows->where('needs_follow_up', true)->sortByDesc('open_alerts')->take(8)->values(),
            'upcoming' => $rows->filter(fn ($r) => $r['next_exam'])->sortBy(fn ($r) => $r['next_exam']['days'])->take(8)->values(),
            'alerts' => StudentAlert::unresolved()->with('student')
                ->whereIn('student_id', $rows->pluck('profile.user_id'))
                ->latest('generated_at')->limit(6)->get(),
        ]);
    }
}
