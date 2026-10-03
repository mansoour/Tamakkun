<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ChallengeAnswer;
use App\Models\ChallengeQuestion;
use App\Models\Motivation;
use App\Services\DailyChallengeService;
use App\Services\MotivationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * تحدي اليوم، دفعة اليوم and الإشعارات for the signed-in student.
 */
class EngagementController extends Controller
{
    public function challenge(Request $request, DailyChallengeService $challenges): View
    {
        $challenge = $challenges->today();
        $student = $request->user();

        return view('student.challenge', [
            'challenge' => $challenge,
            'answers' => $challenge ? $challenges->answersFor($student, $challenge->questions->pluck('id')) : collect(),
            'history' => ChallengeAnswer::where('student_id', $student->id)
                ->whereDate('answered_at', '<', today())
                ->with('question')->latest('answered_at')->limit(10)->get(),
        ]);
    }

    public function answer(Request $request, ChallengeQuestion $question, DailyChallengeService $challenges): RedirectResponse
    {
        $request->validate(['option_id' => ['required', 'integer']], [], ['option_id' => 'الإجابة']);

        try {
            $result = $challenges->answer($request->user(), $question, $request->integer('option_id'));
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['answer' => $e->getMessage()]);
        }

        if ($result['already']) {
            return to_route('student.challenge')->withErrors(['answer' => 'أجبتِ عن هذا السؤال مسبقًا.']);
        }

        return to_route('student.challenge')->with(
            $result['answer']->is_correct ? 'success' : 'info',
            ($result['answer']->is_correct ? 'إجابة صحيحة! ' : 'إجابة غير صحيحة، راجعي الشرح. ')."سلسلة أيامك الآن: {$result['streak']}.",
        );
    }

    public function motivation(MotivationService $motivations): View
    {
        $today = $motivations->today();

        return view('student.motivation', [
            'today' => $today,
            'recent' => Motivation::available()->when($today, fn ($q) => $q->whereKeyNot($today->id))
                ->latest('publish_date')->latest('id')->limit(6)->get(),
        ]);
    }

    public function notifications(Request $request): View
    {
        return view('student.notifications', [
            'notifications' => $request->user()->notifications()->paginate(20),
        ]);
    }

    public function markNotificationsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'تم تعليم كل الإشعارات كمقروءة.');
    }

    public function openNotification(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        // Only follow links back into this site (exact scheme + host), never elsewhere.
        $internal = is_string($url)
            && parse_url($url, PHP_URL_HOST) === parse_url(url('/'), PHP_URL_HOST)
            && parse_url($url, PHP_URL_SCHEME) === parse_url(url('/'), PHP_URL_SCHEME);

        return $internal ? redirect($url) : to_route('student.notifications');
    }
}
