<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Services\QuizService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class QuizController extends Controller
{
    public function __construct(private readonly QuizService $quizzes) {}

    public function submit(Request $request, Content $content): RedirectResponse
    {
        abort_unless($content->isVisible() && $content->quiz, 404);

        $request->validate(['answers' => ['required', 'array'], 'answers.*' => ['integer']], [], ['answers' => 'الإجابات']);

        try {
            $attempt = $this->quizzes->submit($request->user(), $content->quiz, $request->input('answers'));
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['answers' => $e->getMessage()])->withInput();
        }

        $message = "نتيجتك: {$attempt->correct_count} من {$attempt->question_count} ({$attempt->percentage}%).";

        return to_route('student.content.show', $content)->withFragment('quiz-result')
            ->with($attempt->passed ? 'success' : 'info', $attempt->passed
                ? "أحسنتِ! {$message} سُجّل إنجاز هذا الاختبار."
                : "{$message} راجعي الشرح ثم أعيدي المحاولة.");
    }
}
