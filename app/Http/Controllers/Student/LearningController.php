<?php

namespace App\Http\Controllers\Student;

use App\Enums\ContentSection;
use App\Enums\ContentStage;
use App\Enums\ContentType;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Content;
use App\Models\Source;
use App\Models\Subject;
use App\Services\ContentCompletionService;
use App\Services\FavoriteService;
use App\Services\StudentProgressService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The student's learning areas: القدرات الكمي، القدرات اللفظي، التحصيلي،
 * مكتبة المقاطع and the content page. Only visible content is ever shown.
 */
class LearningController extends Controller
{
    public function __construct(
        private readonly StudentProgressService $progress,
        private readonly ContentCompletionService $completion,
        private readonly FavoriteService $favorites,
    ) {}

    public function quantitative(Request $request): View
    {
        return $this->qudurat($request, ContentSection::QUANTITATIVE);
    }

    public function verbal(Request $request): View
    {
        return $this->qudurat($request, ContentSection::VERBAL);
    }

    public function achievement(): View
    {
        return view('student.learning.achievement', [
            'subjects' => Subject::where('is_active', true)->orderBy('sort_order')
                ->withCount(['contents' => fn ($q) => $q->visible()])->get(),
        ]);
    }

    public function subject(Request $request, Subject $subject): View
    {
        abort_unless($subject->is_active, 404);

        $contents = Content::visible()->where('subject_id', $subject->id)->with('source')->ordered()->get();

        return view('student.learning.subject', [
            'statuses' => $this->progress->statuses($request->user(), $contents->pluck('id')),
            'subject' => $subject->load('chapters.topics'),
            'contentsByChapter' => $contents->groupBy(fn (Content $c) => $c->chapter_id ?? 0),
        ]);
    }

    public function videos(Request $request): View
    {
        $search = trim((string) $request->input('q'));

        $videos = Content::visible()->where('content_type', ContentType::VIDEO)
            ->when($request->input('section'), fn ($q, $section) => $q->where('section', $section))
            ->when($search !== '', fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->with(['source', 'category', 'subject'])
            ->ordered()->paginate(24)->withQueryString();

        return view('student.learning.videos', [
            'videos' => $videos,
            'statuses' => $this->progress->statuses($request->user(), $videos->pluck('id')),
            'sections' => ContentSection::cases(),
        ]);
    }

    public function show(Request $request, Content $content): View
    {
        abort_unless($content->isVisible(), 404);

        $this->completion->touchViewed($request->user(), $content);

        return view('student.learning.show', [
            'content' => $content->load(['source', 'category', 'subject', 'chapter', 'topic']),
            'progress' => $this->completion->progressFor($request->user(), $content),
            'isFavorite' => $this->favorites->isFavorite($request->user(), $content),
        ]);
    }

    private function qudurat(Request $request, ContentSection $section): View
    {
        $search = trim((string) $request->input('q'));

        $contents = Content::visible()->where('section', $section)
            ->when($request->input('stage'), fn ($q, $stage) => $q->where('stage', $stage))
            ->when($request->integer('source_id'), fn ($q, $id) => $q->where('source_id', $id))
            ->when($search !== '', fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->with('source')->ordered()->get();

        return view('student.learning.section', [
            'section' => $section,
            'categories' => Category::where('section', $section)->where('is_active', true)->ordered()->get(),
            'contentsByCategory' => $contents->groupBy('category_id'),
            'statuses' => $this->progress->statuses($request->user(), $contents->pluck('id')),
            'total' => $contents->count(),
            'stages' => ContentStage::cases(),
            'sources' => Source::active()->whereHas('contents', fn ($q) => $q->visible()->where('section', $section))->orderBy('name')->pluck('name', 'id'),
            'filtered' => $request->hasAny(['stage', 'source_id', 'q']) && $request->collect()->filter()->isNotEmpty(),
        ]);
    }
}
