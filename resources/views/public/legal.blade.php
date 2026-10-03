<x-public-layout :title="$page['title']">
    <article class="card mx-auto max-w-3xl p-6 sm:p-10">
        <h1 class="font-heading text-2xl font-bold text-ink sm:text-3xl">{{ $page['title'] }}</h1>

        @if ($page['paragraphs'] === [] && ! $page['url'])
            <p class="mt-6 flex items-center gap-2 text-muted">
                <x-badge>قريبًا</x-badge>
                تُعدّ الإدارة هذه الصفحة حاليًا.
            </p>
        @else
            <div class="mt-6 space-y-4 leading-loose text-ink">
                @foreach ($page['paragraphs'] as $paragraph)
                    <p>{!! nl2br(e($paragraph)) !!}</p>
                @endforeach
            </div>

            @if ($page['url'])
                <p class="mt-6">
                    <a href="{{ $page['url'] }}" target="_blank" rel="noopener noreferrer" class="btn-secondary">
                        <x-icon name="link" class="h-5 w-5" />
                        النسخة الكاملة
                        <span class="sr-only">(يفتح في نافذة جديدة)</span>
                    </a>
                </p>
            @endif
        @endif
    </article>
</x-public-layout>
