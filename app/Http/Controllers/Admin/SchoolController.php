<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SchoolRequest;
use App\Models\School;
use App\Services\SchoolStructureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SchoolController extends Controller
{
    public function __construct(private readonly SchoolStructureService $structure) {}

    public function index(): View
    {
        return view('admin.schools.index', [
            'schools' => School::withCount(['academicYears', 'studentProfiles', 'counselorProfiles'])->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.schools.form', ['school' => new School(['is_active' => true])]);
    }

    public function store(SchoolRequest $request): RedirectResponse
    {
        $this->structure->create(School::class, $request->validated());

        return to_route('admin.schools.index')->with('success', 'تمت إضافة المدرسة.');
    }

    public function edit(School $school): View
    {
        return view('admin.schools.form', ['school' => $school]);
    }

    public function update(SchoolRequest $request, School $school): RedirectResponse
    {
        $this->structure->update($school, $request->validated());

        return to_route('admin.schools.index')->with('success', 'تم حفظ التعديلات.');
    }

    public function destroy(School $school): RedirectResponse
    {
        $this->structure->delete($school);

        return to_route('admin.schools.index')->with('success', 'تم حذف المدرسة.');
    }
}
