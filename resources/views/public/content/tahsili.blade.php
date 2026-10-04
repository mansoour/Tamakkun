<x-public-layout title="التحصيلي">
    <a href="{{ route('browse') }}" class="btn-ghost -ms-3 mb-2 px-3"><x-icon name="chevron-left" class="h-4 w-4 rotate-180" /> المحتوى التعليمي</a>
    <x-page-header title="التحصيلي" description="فصول المقرر للصفوف الثلاثة، ولكل درس لعبة وحلّها، مع الدورات والتجميعات والاختبارات." />

    @include('public.content._join')

    @foreach ($subjects as $subject)
        <h2 class="mt-8 font-heading text-xl font-extrabold text-ink">{{ $subject->name }}</h2>
        <div class="mt-4 space-y-4">
            @foreach ($subject->chapters as $chapter)
                @php($items = $byChapter->get($chapter->id, collect()))
                @continue($items->isEmpty())
                <section class="card p-5" x-data="{ open: false }" aria-labelledby="chapter-{{ $chapter->id }}">
                    <button type="button" class="flex w-full items-center justify-between gap-3 text-start" x-on:click="open = ! open" :aria-expanded="open.toString()">
                        <span>
                            <span id="chapter-{{ $chapter->id }}" class="block font-heading font-bold text-ink">{{ $chapter->name }}</span>
                            <span class="text-sm text-muted">{{ $items->count() }} عنصر</span>
                        </span>
                        <x-icon name="chevron-down" class="h-5 w-5 shrink-0 text-muted transition" x-bind:class="open ? 'rotate-180' : ''" />
                    </button>
                    <div x-show="open" x-cloak class="mt-4 space-y-4">
                        @foreach ($items->groupBy(fn ($c) => $c->topic_id ?? 0) as $topicId => $topicItems)
                            @if ($topic = $chapter->topics->firstWhere('id', $topicId))
                                <h3 class="text-sm font-bold text-brand-800">{{ $topic->name }}</h3>
                            @endif
                            <div class="grid gap-2 md:grid-cols-2">
                                @foreach ($topicItems as $content)
                                    <x-public-content-item :content="$content" />
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @endforeach
</x-public-layout>
