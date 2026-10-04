<?php

namespace App\Http\Controllers;

use App\Enums\ContentSection;
use App\Models\Category;
use App\Models\Content;
use App\Models\Subject;
use Illuminate\View\View;

/**
 * Public catalogue (/content): visitors browse sections, categories, chapters
 * and lesson titles. No video or external URL is ever sent to a guest; every
 * item leads to sign-up or login, and signed-in students open it directly.
 */
class PublicContentController extends Controller
{
    public function index(): View
    {
        $counts = Content::visible()->toBase()->selectRaw('section, count(*) as n')->groupBy('section')->pluck('n', 'section');

        return view('public.content.index', [
            'sections' => collect(ContentSection::cases())->map(fn (ContentSection $section) => [
                'section' => $section,
                'count' => (int) ($counts[$section->value] ?? 0),
                'groups' => $section === ContentSection::TAHSILI
                    ? Subject::where('is_active', true)->whereHas('contents', fn ($q) => $q->visible())->orderBy('sort_order')->pluck('name')
                    : Category::where('section', $section)->where('is_active', true)->whereHas('contents', fn ($q) => $q->visible())->ordered()->pluck('name'),
            ]),
        ]);
    }

    public function section(string $section): View
    {
        $section = ContentSection::tryFrom($section) ?? abort(404);

        if ($section === ContentSection::TAHSILI) {
            $contents = Content::visible()->where('section', $section)->ordered()
                ->get(['id', 'title', 'slug', 'content_type', 'stage', 'duration_seconds', 'subject_id', 'chapter_id', 'topic_id']);

            return view('public.content.tahsili', [
                'section' => $section,
                'subjects' => Subject::where('is_active', true)->orderBy('sort_order')->with('chapters.topics')->get()
                    ->filter(fn (Subject $s) => $contents->contains('subject_id', $s->id)),
                'byChapter' => $contents->groupBy(fn (Content $c) => $c->chapter_id ?? 0),
            ]);
        }

        $contents = Content::visible()->where('section', $section)->ordered()
            ->get(['id', 'title', 'slug', 'content_type', 'stage', 'duration_seconds', 'category_id']);

        return view('public.content.section', [
            'section' => $section,
            'categories' => Category::where('section', $section)->where('is_active', true)->ordered()->get()
                ->filter(fn (Category $c) => $contents->contains('category_id', $c->id)),
            'byCategory' => $contents->groupBy('category_id'),
        ]);
    }
}
