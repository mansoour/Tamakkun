<x-public-layout :title="$section->label()">
    <a href="{{ route('browse') }}" class="btn-ghost -ms-3 mb-2 px-3"><x-icon name="chevron-left" class="h-4 w-4 rotate-180" /> المحتوى التعليمي</a>
    <x-page-header :title="$section->label()" description="تأسيس ← تدريب ← إتقان ← مراجعة. كل قسم مرتّب بالترتيب المقترح للمذاكرة." />

    @include('public.content._join')

    <div class="mt-6 space-y-4">
        @foreach ($categories as $category)
            @php($items = $byCategory->get($category->id, collect()))
            <section class="card p-5" x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }" aria-labelledby="cat-{{ $category->id }}">
                <button type="button" class="flex w-full items-center justify-between gap-3 text-start" x-on:click="open = ! open" :aria-expanded="open.toString()">
                    <span>
                        <span id="cat-{{ $category->id }}" class="block font-heading text-lg font-bold text-ink">{{ $category->name }}</span>
                        <span class="text-sm text-muted">{{ $items->count() }} عنصر</span>
                    </span>
                    <x-icon name="chevron-down" class="h-5 w-5 shrink-0 text-muted transition" x-bind:class="open ? 'rotate-180' : ''" />
                </button>
                @if ($category->description)
                    <p class="mt-2 text-sm leading-relaxed text-muted">{{ $category->description }}</p>
                @endif
                <div x-show="open" @if (! $loop->first) x-cloak @endif class="mt-4 grid gap-2 md:grid-cols-2">
                    @foreach ($items as $content)
                        <x-public-content-item :content="$content" />
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</x-public-layout>
