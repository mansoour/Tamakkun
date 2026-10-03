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
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The student's learning areas: القدرات الكمي، القدرات اللفظي، التحصيلي،
 * مكتبة المقاطع and the content page. Only visible content is ever shown.
 */
class LearningController extends Controller
{
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

    public function subject(Subject $subject): View
    {
        abort_unless($subject->is_active, 404);

        $contents = Content::visible()->where('subject_id', $subject->id)->with('source')->ordered()->get();

        return view('student.learning.subject', [
            'subject' => $subject->load('chapters.topics'),
            'contentsByChapter' => $contents->groupBy(fn (Content $c) => $c->chapter_id ?? 0),
        ]);
    }

    public function videos(Request $request): View
    {
        $search = trim((string) $request->input('q'));

        return view('student.learning.videos', [
            'videos' => Content::visible()->where('content_type', ContentType::VIDEO)
                ->when($request->input('section'), fn ($q, $section) => $q->where('section', $section))
                ->when($search !== '', fn ($q) => $q->where('title', 'like', "%{$search}%"))
                ->with(['source', 'category', 'subject'])
                ->ordered()->paginate(24)->withQueryString(),
            'sections' => ContentSection::cases(),
        ]);
    }

    public function show(Content $content): View
    {
        abort_unless($content->isVisible(), 404);

        return view('student.learning.show', [
            'content' => $content->load(['source', 'category', 'subject', 'chapter', 'topic']),
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
            'total' => $contents->count(),
            'stages' => ContentStage::cases(),
            'sources' => Source::active()->whereHas('contents', fn ($q) => $q->visible()->where('section', $section))->orderBy('name')->pluck('name', 'id'),
            'filtered' => $request->hasAny(['stage', 'source_id', 'q']) && $request->collect()->filter()->isNotEmpty(),
        ]);
    }
}
