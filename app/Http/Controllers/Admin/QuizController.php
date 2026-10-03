<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentType;
use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\QuizRequest;
use App\Models\Content;
use App\Services\QuizService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function __construct(private readonly QuizService $quizzes) {}

    public function edit(Content $content): View
    {
        abort_unless($content->content_type === ContentType::QUIZ, 404);

        $quiz = $content->quiz()->with('questions.options')->withCount('attempts')->first();

        $questions = $quiz?->questions->map(fn ($q) => [
            'question_type' => $q->question_type->value,
            'prompt' => $q->prompt,
            'explanation' => $q->explanation,
            'options' => array_pad($q->options->pluck('label')->all(), 4, ''),
            'correct' => (int) $q->options->search(fn ($o) => $o->is_correct),
        ])->all() ?: [$this->blankQuestion()];

        $questions = array_values(old('questions', $questions));

        return view('admin.quizzes.edit', [
            'content' => $content,
            'passPercentage' => old('pass_percentage', $quiz->pass_percentage ?? 60),
            'attemptsCount' => $quiz->attempts_count ?? 0,
            'questions' => array_pad($questions, QuizService::MAX_QUESTIONS, $this->blankQuestion()),
            'visibleCount' => count($questions),
            'types' => QuestionType::cases(),
        ]);
    }

    public function update(QuizRequest $request, Content $content): RedirectResponse
    {
        abort_unless($content->content_type === ContentType::QUIZ, 404);

        $this->quizzes->saveQuestions($content, $request->validated(), $request->user());

        return to_route('admin.content.quiz.edit', $content)->with('success', 'تم حفظ أسئلة الاختبار.');
    }

    /**
     * @return array{question_type: string, prompt: string, explanation: string, options: list<string>, correct: int}
     */
    private function blankQuestion(): array
    {
        return ['question_type' => QuestionType::MULTIPLE_CHOICE->value, 'prompt' => '', 'explanation' => '', 'options' => ['', '', '', ''], 'correct' => 0];
    }
}
