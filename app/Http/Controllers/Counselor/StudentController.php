<?php

namespace App\Http\Controllers\Counselor;

use App\Http\Controllers\Controller;
use App\Models\StudentProfile;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The counselor's own students. Full follow-up tools arrive in v0.6.
 */
class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', StudentProfile::class);

        $search = trim((string) $request->input('q'));

        return view('counselor.students.index', [
            'students' => $request->user()->assignedStudents()
                ->with(['user', 'classroom.grade'])
                ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                    ->where('student_code', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"))))
                ->join('users', 'users.id', '=', 'student_profiles.user_id')
                ->orderBy('users.name')
                ->select('student_profiles.*')
                ->paginate(25)->withQueryString(),
        ]);
    }

    public function show(StudentProfile $student): View
    {
        $this->authorize('view', $student);

        return view('counselor.students.show', [
            'student' => $student->load(['user', 'school', 'classroom.grade.academicYear']),
        ]);
    }
}
