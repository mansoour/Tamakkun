<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ContentDifficulty;
use App\Enums\ContentSection;
use App\Enums\ContentStage;
use App\Enums\ContentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ContentRequest;
use App\Models\Category;
use App\Models\Content;
use App\Models\Source;
use App\Models\Subject;
use App\Services\ContentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function __construct(private readonly ContentService $contents) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q'));

        return view('admin.content.index', [
            'contents' => Content::with(['category', 'subject', 'source'])
                ->when($search !== '', fn ($q) => $q->where('title', 'like', "%{$search}%"))
                ->when($request->input('section'), fn ($q, $v) => $q->where('section', $v))
                ->when($request->integer('source_id'), fn ($q, $v) => $q->where('source_id', $v))
                ->when($request->input('stage'), fn ($q, $v) => $q->where('stage', $v))
                ->when($request->integer('subject_id'), fn ($q, $v) => $q->where('subject_id', $v))
                ->when($request->input('status'), fn ($q, $status) => match ($status) {
                    'published' => $q->whereNull('archived_at')->where('is_published', true),
                    'draft' => $q->whereNull('archived_at')->where('is_published', false),
                    'archived' => $q->whereNotNull('archived_at'),
                    default => $q,
                })
                ->orderBy('section')->ordered()
                ->paginate(25)->withQueryString(),
            'sources' => Source::orderBy('name')->pluck('name', 'id'),
            'subjects' => Subject::orderBy('sort_order')->pluck('name', 'id'),
        ]);
    }

    public function create(Request $request): View
    {
        return $this->form(new Content([
            'section' => $request->input('section', ContentSection::QUANTITATIVE->value),
            'content_type' => ContentType::VIDEO,
            'sort_order' => 0,
        ]));
    }

    public function store(ContentRequest $request): RedirectResponse
    {
        $this->contents->create($request->validated(), $request->file('thumbnail'), $request->user());

        return to_route('admin.content.index')->with('success', 'تمت إضافة المحتوى.');
    }

    public function edit(Content $content): View
    {
        return $this->form($content);
    }

    public function update(ContentRequest $request, Content $content): RedirectResponse
    {
        $this->contents->update($content, $request->validated(), $request->file('thumbnail'), $request->boolean('remove_thumbnail'));

        return to_route('admin.content.index')->with('success', 'تم حفظ المحتوى.');
    }

    public function publish(Content $content): RedirectResponse
    {
        $this->contents->publish($content);

        return back()->with('success', 'تم نشر المحتوى.');
    }

    public function unpublish(Content $content): RedirectResponse
    {
        $this->contents->unpublish($content);

        return back()->with('success', 'تم إلغاء نشر المحتوى.');
    }

    public function archive(Content $content): RedirectResponse
    {
        $this->contents->archive($content);

        return back()->with('success', 'تمت أرشفة المحتوى.');
    }

    public function restore(Content $content): RedirectResponse
    {
        $this->contents->restore($content);

        return back()->with('success', 'تمت استعادة المحتوى كمسودة.');
    }

    private function form(Content $content): View
    {
        $subjects = Subject::with('chapters.topics')->orderBy('sort_order')->get();

        return view('admin.content.form', [
            'content' => $content,
            'sections' => ContentSection::cases(),
            'types' => ContentType::cases(),
            'stages' => ContentStage::cases(),
            'difficulties' => ContentDifficulty::cases(),
            'sources' => Source::orderBy('name')->pluck('name', 'id'),
            'subjects' => $subjects->pluck('name', 'id'),
            'categoriesBySection' => Category::ordered()->get()->groupBy(fn (Category $c) => $c->section->value)
                ->map(fn ($group) => $group->map(fn (Category $c) => ['id' => $c->id, 'label' => $c->name])->values()),
            'chaptersBySubject' => $subjects->mapWithKeys(fn (Subject $s) => [$s->id => $s->chapters->map(fn ($c) => ['id' => $c->id, 'label' => $c->name])->values()]),
            'topicsByChapter' => $subjects->flatMap->chapters->mapWithKeys(fn ($c) => [$c->id => $c->topics->map(fn ($t) => ['id' => $t->id, 'label' => $t->name])->values()]),
        ]);
    }
}
