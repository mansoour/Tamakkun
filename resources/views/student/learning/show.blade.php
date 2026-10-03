@php
    $back = match ($content->section) {
        \App\Enums\ContentSection::QUANTITATIVE => route('student.quantitative'),
        \App\Enums\ContentSection::VERBAL => route('student.verbal'),
        \App\Enums\ContentSection::TAHSILI => $content->subject ? route('student.achievement.subject', $content->subject) : route('student.achievement'),
    };
@endphp

<x-app-layout area="student" :title="$content->title">
    <a href="{{ $back }}" class="btn-ghost -ms-3 mb-2 px-3">
        <x-icon name="chevron-left" class="h-4 w-4 rotate-180" /> {{ $content->section->label() }}
    </a>

    <article class="card overflow-hidden">
        @if ($content->embedUrl())
            <div class="aspect-video w-full bg-ink">
                <iframe src="{{ $content->embedUrl() }}" title="{{ $content->title }}" class="h-full w-full"
                    allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen"
                    referrerpolicy="strict-origin-when-cross-origin" loading="lazy" allowfullscreen></iframe>
            </div>
        @endif

        <div class="space-y-4 p-6">
            <div class="flex flex-wrap gap-1.5">
                <x-badge>{{ $content->content_type->label() }}</x-badge>
                @if ($content->stage) <x-badge color="gray">{{ $content->stage->label() }}</x-badge> @endif
                @if ($content->difficulty) <x-badge color="gray">{{ $content->difficulty->label() }}</x-badge> @endif
                @if ($content->durationMinutes()) <x-badge color="gray">{{ $content->durationMinutes() }} دقيقة</x-badge> @endif
                <x-source-badge :source="$content->source" />
            </div>

            <h1 class="text-2xl font-bold text-ink">{{ $content->title }}</h1>

            <p class="text-sm text-muted">
                {{ $content->category?->name ?? collect([$content->subject?->name, $content->chapter?->name, $content->topic?->name])->filter()->join(' — ') }}
            </p>

            @if ($content->description)
                <p class="leading-relaxed text-ink">{{ $content->description }}</p>
            @endif

            @if ($content->body)
                <div class="prose max-w-none leading-loose text-ink">{!! nl2br(e($content->body)) !!}</div>
            @endif

            @if ($content->external_url)
                <a href="{{ $content->external_url }}" target="_blank" rel="noopener noreferrer" class="btn-primary">
                    <x-icon name="link" /> فتح المصدر
                    <span class="sr-only">(يفتح في نافذة جديدة)</span>
                </a>
            @endif

            @if ($content->source)
                <p class="border-t border-line pt-4 text-xs text-muted">
                    المصدر: {{ $content->source->name }}. تمكّن منصة متابعة وتنظيم ولا تعيد نشر المحتوى المملوك لغيرها.
                </p>
            @endif
        </div>
    </article>
</x-app-layout>
