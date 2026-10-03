<x-app-layout area="student" :title="$subject->name">
    <a href="{{ route('student.achievement') }}" class="btn-ghost -ms-3 mb-2 px-3">
        <x-icon name="chevron-left" class="h-4 w-4 rotate-180" /> التحصيلي
    </a>
    <x-page-header :title="$subject->name" />

    @if ($contentsByChapter->isEmpty())
        <x-empty-state class="mt-6" icon="beaker" title="لا يوجد محتوى منشور لهذه المادة بعد" />
    @else
        <div class="mt-6 space-y-6">
            @foreach ($subject->chapters as $chapter)
                @php($items = $contentsByChapter->get($chapter->id, collect()))
                @continue($items->isEmpty())
                <section class="card p-5" x-data="{ open: true }" aria-labelledby="chapter-{{ $chapter->id }}">
                    <button type="button" class="flex w-full items-center justify-between gap-3 text-start" x-on:click="open = ! open" :aria-expanded="open.toString()" aria-expanded="true">
                        <h2 id="chapter-{{ $chapter->id }}" class="text-lg font-semibold text-ink">{{ $chapter->name }}</h2>
                        <x-icon name="chevron-down" class="h-5 w-5 text-muted transition" x-bind:class="open ? 'rotate-180' : ''" />
                    </button>
                    <div x-show="open" class="mt-4 space-y-4">
                        @foreach ($items->groupBy(fn ($c) => $c->topic_id ?? 0) as $topicId => $topicItems)
                            @php($topic = $chapter->topics->firstWhere('id', $topicId))
                            <div>
                                @if ($topic)
                                    <h3 class="mb-2 text-sm font-semibold text-brand-800">{{ $topic->name }}</h3>
                                @endif
                                <div class="grid gap-3 md:grid-cols-2">
                                    @foreach ($topicItems as $content)
                                        <x-content-card :content="$content" />
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach

            @if ($contentsByChapter->has(0))
                <section aria-labelledby="general-title">
                    <h2 id="general-title" class="text-lg font-semibold text-ink">محتوى عام للمادة</h2>
                    <div class="mt-3 grid gap-3 md:grid-cols-2">
                        @foreach ($contentsByChapter->get(0) as $content)
                            <x-content-card :content="$content" />
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    @endif
</x-app-layout>
