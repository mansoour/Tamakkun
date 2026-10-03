<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GradeRequest;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Services\SchoolStructureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class GradeController extends Controller
{
    public function __construct(private readonly SchoolStructureService $structure) {}

    public function index(Request $request): View
    {
        return view('admin.grades.index', [
            'grades' => Grade::with('academicYear.school')->withCount('classrooms')
                ->when($request->integer('academic_year_id'), fn ($q, $id) => $q->where('academic_year_id', $id))
                ->orderBy('academic_year_id')->orderBy('sort_order')->orderBy('name')
                ->paginate(20)->withQueryString(),
            'years' => $this->yearOptions(),
        ]);
    }

    public function create(Request $request): View
    {
        return $this->form(new Grade(['academic_year_id' => $request->integer('academic_year_id') ?: null, 'level' => 12]));
    }

    public function store(GradeRequest $request): RedirectResponse
    {
        $this->structure->create(Grade::class, $request->validated());

        return to_route('admin.grades.index')->with('success', 'تمت إضافة الصف.');
    }

    public function edit(Grade $grade): View
    {
        return $this->form($grade);
    }

    public function update(GradeRequest $request, Grade $grade): RedirectResponse
    {
        $this->structure->update($grade, $request->validated());

        return to_route('admin.grades.index')->with('success', 'تم حفظ التعديلات.');
    }

    public function destroy(Grade $grade): RedirectResponse
    {
        $this->structure->delete($grade);

        return to_route('admin.grades.index')->with('success', 'تم حذف الصف.');
    }

    private function form(Grade $grade): View
    {
        return view('admin.grades.form', ['grade' => $grade, 'years' => $this->yearOptions()]);
    }

    /**
     * @return Collection<int, string>
     */
    private function yearOptions(): Collection
    {
        return AcademicYear::with('school')->orderByDesc('name')->get()
            ->mapWithKeys(fn (AcademicYear $year) => [$year->id => $year->fullName()]);
    }
}
