<x-app-layout area="student" title="دفعة اليوم">
    <x-page-header title="دفعة اليوم" description="خطوة صغيرة مفيدة كل يوم." />

    @if (! $today)
        <x-empty-state class="mt-6" icon="sparkles" title="لا توجد دفعة لهذا اليوم بعد" />
    @else
        <article class="card mt-6 overflow-hidden">
            @if ($today->embedUrl())
                <div class="aspect-video w-full bg-ink">
                    <iframe src="{{ $today->embedUrl() }}" title="{{ $today->title }}" class="h-full w-full" loading="lazy"
                        allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                </div>
            @elseif ($today->imageUrl())
                <img src="{{ $today->imageUrl() }}" alt="" class="max-h-96 w-full object-cover">
            @endif
            <div class="space-y-3 p-6">
                <x-badge><x-icon :name="$today->media_type->icon()" class="h-3.5 w-3.5" /> {{ $today->media_type->label() }}</x-badge>
                <h2 class="text-2xl font-bold text-ink">{{ $today->title }}</h2>
                <p class="whitespace-pre-line text-lg leading-loose text-ink">{{ $today->content }}</p>
            </div>
        </article>
    @endif

    @if ($recent->isNotEmpty())
        <section class="mt-8" aria-labelledby="recent-title">
            <h2 id="recent-title" class="text-lg font-semibold text-ink">دفعات سابقة</h2>
            <div class="mt-3 grid gap-3 md:grid-cols-2">
                @foreach ($recent as $item)
                    <article class="card p-4">
                        <x-badge color="gray">{{ $item->media_type->label() }}</x-badge>
                        <h3 class="mt-2 font-heading font-semibold text-ink">{{ $item->title }}</h3>
                        <p class="mt-1 line-clamp-3 whitespace-pre-line text-sm text-muted">{{ $item->content }}</p>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</x-app-layout>
