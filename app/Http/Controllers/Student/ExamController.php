<?php

namespace App\Http\Controllers\Student;

use App\Enums\ExamBookingStatus;
use App\Enums\ExamType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreExamAttemptRequest;
use App\Http\Requests\Student\UpdateExamAttemptRequest;
use App\Models\ExamAttempt;
use App\Models\ImportantLink;
use App\Services\ExamAttemptService;
use App\Services\ExamProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * موعدي ودرجتي — the student's own exam attempts.
 */
class ExamController extends Controller
{
    public function __construct(private readonly ExamAttemptService $attempts) {}

    public function index(Request $request, ExamProgressService $exams): View
    {
        $summary = $exams->summary($request->user());

        return view('student.exams.index', [
            'summary' => $summary,
            'next' => $exams->nextExam($summary),
            'hasOfficialLinks' => ImportantLink::where('is_active', true)->where('is_official', true)->exists(),
        ]);
    }

    public function create(Request $request): View
    {
        return $this->form(new ExamAttempt([
            'exam_type' => ExamType::tryFrom((string) $request->input('type')) ?? ExamType::QUDURAT,
            'booking_status' => ExamBookingStatus::NOT_BOOKED,
        ]));
    }

    public function store(StoreExamAttemptRequest $request): RedirectResponse
    {
        $this->attempts->create($request->user(), $request->validated());

        return to_route('student.exams.index')->with('success', 'تم حفظ الاختبار.');
    }

    public function edit(ExamAttempt $attempt): View
    {
        $this->authorize('update', $attempt);

        return $this->form($attempt);
    }

    public function update(UpdateExamAttemptRequest $request, ExamAttempt $attempt): RedirectResponse
    {
        $this->attempts->update($attempt, $request->validated());

        return to_route('student.exams.index')->with('success', 'تم تحديث الاختبار.');
    }

    public function destroy(ExamAttempt $attempt): RedirectResponse
    {
        $this->authorize('delete', $attempt);
        $this->attempts->delete($attempt);

        return to_route('student.exams.index')->with('success', 'تم حذف الاختبار.');
    }

    private function form(ExamAttempt $attempt): View
    {
        return view('student.exams.form', [
            'attempt' => $attempt,
            'types' => ExamType::cases(),
            'statuses' => ExamBookingStatus::cases(),
        ]);
    }
}
