<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentSection;
use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreChallengeRequest;
use App\Models\ChallengeAnswer;
use App\Models\DailyChallenge;
use App\Services\DailyChallengeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use InvalidArgumentException;

class ChallengeController extends Controller
{
    public function __construct(private readonly DailyChallengeService $challenges) {}

    public function index(): View
    {
        return view('admin.challenges.index', [
            'challenges' => DailyChallenge::withCount('questions')
                ->addSelect(['answers_count' => ChallengeAnswer::selectRaw('count(*)')
                    ->join('challenge_questions', 'challenge_questions.id', '=', 'challenge_answers.challenge_question_id')
                    ->whereColumn('challenge_questions.daily_challenge_id', 'daily_challenges.id')])
                ->latest('challenge_date')->paginate(20),
        ]);
    }

    public function create(): View
    {
        $challenge = new DailyChallenge(['challenge_date' => DailyChallenge::max('challenge_date')
            ? now()->max(Carbon::parse(DailyChallenge::max('challenge_date'))->addDay())
            : today()]);

        return $this->form($challenge, [
            ['section' => ContentSection::QUANTITATIVE->value, 'question_type' => QuestionType::MULTIPLE_CHOICE->value, 'prompt' => '', 'explanation' => '', 'options' => ['', '', '', ''], 'correct' => 0],
            ['section' => ContentSection::VERBAL->value, 'question_type' => QuestionType::MULTIPLE_CHOICE->value, 'prompt' => '', 'explanation' => '', 'options' => ['', '', '', ''], 'correct' => 0],
        ]);
    }

    public function store(StoreChallengeRequest $request): RedirectResponse
    {
        $this->challenges->save(null, $request->validated(), $request->user());

        return to_route('admin.challenges.index')->with('success', 'تم حفظ التحدي.');
    }

    public function edit(DailyChallenge $challenge): View
    {
        $questions = $challenge->questions()->with('options')->get()->map(fn ($q) => [
            'section' => $q->section->value,
            'question_type' => $q->question_type->value,
            'prompt' => $q->prompt,
            'explanation' => $q->explanation,
            'options' => array_pad($q->options->pluck('label')->all(), 4, ''),
            'correct' => (int) $q->options->search(fn ($o) => $o->is_correct),
        ])->all();

        return $this->form($challenge, $questions);
    }

    public function update(StoreChallengeRequest $request, DailyChallenge $challenge): RedirectResponse
    {
        try {
            $this->challenges->save($challenge, $request->validated(), $request->user());
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['delete' => $e->getMessage()]);
        }

        return to_route('admin.challenges.index')->with('success', 'تم تحديث التحدي.');
    }

    public function publish(DailyChallenge $challenge): RedirectResponse
    {
        $this->challenges->setPublished($challenge, true);

        return back()->with('success', 'تم نشر التحدي.');
    }

    public function unpublish(DailyChallenge $challenge): RedirectResponse
    {
        $this->challenges->setPublished($challenge, false);

        return back()->with('success', 'تم إلغاء نشر التحدي.');
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     */
    private function form(DailyChallenge $challenge, array $questions): View
    {
        return view('admin.challenges.form', [
            'challenge' => $challenge,
            'questions' => old('questions', $questions),
            'sections' => ContentSection::cases(),
            'types' => QuestionType::cases(),
        ]);
    }
}
