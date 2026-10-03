<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AcademicYearRequest;
use App\Models\AcademicYear;
use App\Models\School;
use App\Services\SchoolStructureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicYearController extends Controller
{
    public function __construct(private readonly SchoolStructureService $structure) {}

    public function index(Request $request): View
    {
        return view('admin.academic-years.index', [
            'years' => AcademicYear::with('school')->withCount('grades')
                ->when($request->integer('school_id'), fn ($q, $id) => $q->where('school_id', $id))
                ->orderByDesc('is_current')->orderByDesc('name')
                ->paginate(20)->withQueryString(),
            'schools' => School::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create(Request $request): View
    {
        return $this->form(new AcademicYear(['school_id' => $request->integer('school_id') ?: null]));
    }

    public function store(AcademicYearRequest $request): RedirectResponse
    {
        $this->structure->create(AcademicYear::class, $request->validated());

        return to_route('admin.academic-years.index')->with('success', 'تمت إضافة العام الدراسي.');
    }

    public function edit(AcademicYear $academicYear): View
    {
        return $this->form($academicYear);
    }

    public function update(AcademicYearRequest $request, AcademicYear $academicYear): RedirectResponse
    {
        $this->structure->update($academicYear, $request->validated());

        return to_route('admin.academic-years.index')->with('success', 'تم حفظ التعديلات.');
    }

    public function destroy(AcademicYear $academicYear): RedirectResponse
    {
        $this->structure->delete($academicYear);

        return to_route('admin.academic-years.index')->with('success', 'تم حذف العام الدراسي.');
    }

    private function form(AcademicYear $year): View
    {
        return view('admin.academic-years.form', [
            'year' => $year,
            'schools' => School::orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
