<?php

namespace App\Http\Controllers\Counselor;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\AlertType;
use App\Http\Controllers\Controller;
use App\Models\StudentAlert;
use App\Models\StudentProfile;
use App\Services\StudentAlertService;
use App\Services\StudentRosterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlertController extends Controller
{
    public function __construct(private readonly StudentAlertService $alerts) {}

    public function index(Request $request, StudentRosterService $roster): View
    {
        $this->authorize('viewAny', StudentProfile::class);

        $status = $request->input('status', 'unresolved');

        return view('counselor.alerts', [
            'alerts' => StudentAlert::with('student.studentProfile')
                ->whereIn('student_id', $roster->scopeFor($request->user())->select('user_id'))
                ->when($status === 'unresolved', fn ($q) => $q->unresolved())
                ->when($status === 'resolved', fn ($q) => $q->where('status', AlertStatus::RESOLVED))
                ->when($request->input('severity'), fn ($q, $v) => $q->where('severity', $v))
                ->when($request->input('type'), fn ($q, $v) => $q->where('alert_type', $v))
                ->latest('generated_at')->paginate(25)->withQueryString(),
            'severities' => AlertSeverity::cases(),
            'types' => AlertType::cases(),
        ]);
    }

    public function acknowledge(StudentAlert $alert): RedirectResponse
    {
        $this->authorize('update', $alert);
        $this->alerts->acknowledge($alert);

        return back()->with('success', 'تم تسجيل الاطلاع على التنبيه.');
    }

    public function resolve(Request $request, StudentAlert $alert): RedirectResponse
    {
        $this->authorize('update', $alert);
        $this->alerts->resolve($alert, $request->user());

        return back()->with('success', 'تم إغلاق التنبيه.');
    }
}
