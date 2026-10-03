<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CounselorRequest;
use App\Models\CounselorProfile;
use App\Models\School;
use App\Services\SchoolMembershipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CounselorController extends Controller
{
    public function __construct(private readonly SchoolMembershipService $membership) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q'));

        return view('admin.counselors.index', [
            'counselors' => CounselorProfile::with(['user' => fn ($q) => $q->withCount('assignedStudents'), 'school'])
                ->when($search !== '', fn ($q) => $q->whereHas('user', fn ($u) => $u
                    ->where('name', 'like', "%{$search}%")->orWhere('username', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
                ->latest()
                ->paginate(25)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new CounselorProfile);
    }

    public function store(CounselorRequest $request): RedirectResponse
    {
        $this->membership->createCounselor($request->validated());

        return to_route('admin.counselors.index')->with('success', 'تمت إضافة الموجهة.');
    }

    public function edit(CounselorProfile $counselor): View
    {
        return $this->form($counselor->load('user'));
    }

    public function update(CounselorRequest $request, CounselorProfile $counselor): RedirectResponse
    {
        $this->membership->updateCounselor($counselor, $request->validated());

        return to_route('admin.counselors.index')->with('success', 'تم حفظ بيانات الموجهة.');
    }

    private function form(CounselorProfile $counselor): View
    {
        return view('admin.counselors.form', [
            'counselor' => $counselor,
            'schools' => School::orderBy('name')->pluck('name', 'id'),
            'statuses' => UserStatus::cases(),
        ]);
    }
}
