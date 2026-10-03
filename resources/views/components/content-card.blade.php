@props(['content'])

<a href="{{ route('student.content.show', $content) }}" {{ $attributes->merge(['class' => 'card group flex gap-4 p-4 transition hover:border-brand-300 hover:shadow-md']) }}>
    @if ($content->thumbnailUrl())
        <img src="{{ $content->thumbnailUrl() }}" alt="" loading="lazy" class="h-16 w-24 shrink-0 rounded-xl object-cover">
    @else
        <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-700">
            <x-icon :name="$content->content_type->icon()" class="h-6 w-6" />
        </span>
    @endif
    <span class="min-w-0 flex-1">
        <span class="block font-heading text-sm font-semibold text-ink group-hover:text-brand-800">{{ $content->title }}</span>
        @if ($content->description)
            <span class="mt-1 line-clamp-2 block text-xs leading-relaxed text-muted">{{ $content->description }}</span>
        @endif
        <span class="mt-2 flex flex-wrap items-center gap-1.5">
            <x-badge>{{ $content->content_type->label() }}</x-badge>
            @if ($content->stage) <x-badge color="gray">{{ $content->stage->label() }}</x-badge> @endif
            @if ($content->durationMinutes()) <x-badge color="gray">{{ $content->durationMinutes() }} د</x-badge> @endif
            <x-source-badge :source="$content->source" />
        </span>
    </span>
</a>
