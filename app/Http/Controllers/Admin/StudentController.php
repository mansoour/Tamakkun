<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StudentRequest;
use App\Models\Classroom;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\SchoolMembershipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function __construct(private readonly SchoolMembershipService $membership) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q'));

        return view('admin.students.index', [
            'students' => StudentProfile::with(['user', 'school', 'classroom.grade', 'counselor'])
                ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                    ->where('student_code', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('username', 'like', "%{$search}%"))))
                ->when($request->integer('school_id'), fn ($q, $id) => $q->where('school_id', $id))
                ->when($request->input('status'), fn ($q, $status) => $q->whereHas('user', fn ($u) => $u->where('status', $status)))
                ->when($request->boolean('unassigned'), fn ($q) => $q->whereNull('counselor_id'))
                ->latest()
                ->paginate(25)->withQueryString(),
            'schools' => School::orderBy('name')->pluck('name', 'id'),
            'statuses' => UserStatus::cases(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', StudentProfile::class);

        return $this->form(new StudentProfile);
    }

    public function store(StudentRequest $request): RedirectResponse
    {
        $this->membership->createStudent($request->validated());

        return to_route('admin.students.index')->with('success', 'تمت إضافة الطالبة.');
    }

    public function edit(StudentProfile $student): View
    {
        $this->authorize('update', $student);

        return $this->form($student->load('user'));
    }

    public function update(StudentRequest $request, StudentProfile $student): RedirectResponse
    {
        $this->membership->updateStudent($student, $request->validated());

        return to_route('admin.students.index')->with('success', 'تم حفظ بيانات الطالبة.');
    }

    private function form(StudentProfile $student): View
    {
        return view('admin.students.form', [
            'student' => $student,
            'schools' => School::orderBy('name')->pluck('name', 'id'),
            'classrooms' => $this->classroomOptions(),
            'counselors' => $this->counselorOptions(),
            'statuses' => UserStatus::cases(),
        ]);
    }

    /**
     * Classrooms grouped by school id, so the form can filter by the selected school.
     *
     * @return Collection<int, Collection<int, array{id: int, label: string}>>
     */
    private function classroomOptions(): Collection
    {
        return Classroom::with('grade.academicYear')->get()
            ->sortBy(fn (Classroom $c) => $c->fullName())
            ->groupBy(fn (Classroom $c) => $c->schoolId())
            ->map(fn ($group) => $group->map(fn (Classroom $c) => [
                'id' => $c->id,
                'label' => "{$c->grade->academicYear->name} — {$c->label()}",
            ])->values());
    }

    /**
     * @return Collection<int, Collection<int, array{id: int, label: string}>>
     */
    private function counselorOptions(): Collection
    {
        return User::role(RoleName::COUNSELOR->value)->with('counselorProfile')->has('counselorProfile')->orderBy('name')->get()
            ->groupBy(fn (User $u) => $u->counselorProfile->school_id)
            ->map(fn ($group) => $group->map(fn (User $u) => ['id' => $u->id, 'label' => $u->name])->values());
    }
}
