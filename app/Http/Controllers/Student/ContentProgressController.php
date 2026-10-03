<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Services\ContentCompletionService;
use App\Services\FavoriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The student's own actions on a content item. They always act on the
 * signed-in student, so no other student's progress can be touched.
 */
class ContentProgressController extends Controller
{
    public function __construct(
        private readonly ContentCompletionService $completion,
        private readonly FavoriteService $favorites,
    ) {}

    public function start(Request $request, Content $content): RedirectResponse
    {
        $this->ensureVisible($content);
        $this->completion->start($request->user(), $content);

        return back()->with('success', 'بدأتِ هذا المحتوى. بالتوفيق!');
    }

    public function complete(Request $request, Content $content): RedirectResponse
    {
        $this->ensureVisible($content);
        $this->completion->complete($request->user(), $content);

        return back()->with('success', 'أحسنتِ! سُجّل إنجاز هذا المحتوى.');
    }

    public function uncomplete(Request $request, Content $content): RedirectResponse
    {
        $this->ensureVisible($content);
        $this->completion->uncomplete($request->user(), $content);

        return back()->with('success', 'أُلغي تسجيل الإنجاز.');
    }

    public function favorite(Request $request, Content $content): RedirectResponse
    {
        $this->ensureVisible($content);
        $added = $this->favorites->toggle($request->user(), $content);

        return back()->with('success', $added ? 'أُضيف إلى المفضلة.' : 'أُزيل من المفضلة.');
    }

    private function ensureVisible(Content $content): void
    {
        abort_unless($content->isVisible(), 404);
    }
}
