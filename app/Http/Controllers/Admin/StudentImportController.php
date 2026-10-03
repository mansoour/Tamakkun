<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportStudentsRequest;
use App\Jobs\ImportStudents;
use App\Models\StudentImport;
use App\Services\StudentImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentImportController extends Controller
{
    public function __construct(private readonly StudentImportService $imports) {}

    public function create(): View
    {
        return view('admin.imports.create', [
            'recent' => StudentImport::with('uploader')->latest()->limit(10)->get(),
            'columns' => StudentImportService::COLUMNS,
            'required' => StudentImportService::REQUIRED_COLUMNS,
        ]);
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function () {
            echo "\xEF\xBB\xBF".implode(',', array_keys(StudentImportService::COLUMNS))."\n";
        }, 'tamakkun-students-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function store(ImportStudentsRequest $request): RedirectResponse
    {
        $import = $this->imports->preview($request->file('file'), $request->user());

        return to_route('admin.imports.show', $import);
    }

    public function show(StudentImport $import): View
    {
        return view('admin.imports.show', [
            'import' => $import,
            'preview' => collect($import->payload ?? [])->take(50),
        ]);
    }

    public function confirm(Request $request, StudentImport $import): RedirectResponse
    {
        try {
            $this->imports->confirm($import);
        } catch (RuntimeException $e) {
            return back()->withErrors(['import' => $e->getMessage()]);
        }

        ImportStudents::dispatch($import);

        return to_route('admin.imports.show', $import);
    }
}
