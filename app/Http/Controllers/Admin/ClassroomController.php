<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ClassroomRequest;
use App\Models\Classroom;
use App\Models\Grade;
use App\Services\SchoolStructureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ClassroomController extends Controller
{
    public function __construct(private readonly SchoolStructureService $structure) {}

    public function index(Request $request): View
    {
        return view('admin.classrooms.index', [
            'classrooms' => Classroom::with('grade.academicYear.school')->withCount('studentProfiles')
                ->when($request->integer('grade_id'), fn ($q, $id) => $q->where('grade_id', $id))
                ->orderBy('grade_id')->orderBy('sort_order')->orderBy('name')
                ->paginate(20)->withQueryString(),
            'grades' => $this->gradeOptions(),
        ]);
    }

    public function create(Request $request): View
    {
        return $this->form(new Classroom(['grade_id' => $request->integer('grade_id') ?: null]));
    }

    public function store(ClassroomRequest $request): RedirectResponse
    {
        $this->structure->create(Classroom::class, $request->validated());

        return to_route('admin.classes.index')->with('success', 'تمت إضافة الفصل.');
    }

    public function edit(Classroom $classroom): View
    {
        return $this->form($classroom);
    }

    public function update(ClassroomRequest $request, Classroom $classroom): RedirectResponse
    {
        $this->structure->update($classroom, $request->validated());

        return to_route('admin.classes.index')->with('success', 'تم حفظ التعديلات.');
    }

    public function destroy(Classroom $classroom): RedirectResponse
    {
        $this->structure->delete($classroom);

        return to_route('admin.classes.index')->with('success', 'تم حذف الفصل.');
    }

    private function form(Classroom $classroom): View
    {
        return view('admin.classrooms.form', ['classroom' => $classroom, 'grades' => $this->gradeOptions()]);
    }

    /**
     * @return Collection<int, string>
     */
    private function gradeOptions(): Collection
    {
        return Grade::with('academicYear.school')->get()
            ->sortBy(fn (Grade $grade) => $grade->fullName())
            ->mapWithKeys(fn (Grade $grade) => [$grade->id => $grade->fullName()]);
    }
}
