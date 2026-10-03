<x-public-layout title="مصادر رسمية">
    <x-page-header title="مصادر رسمية" description="روابط الخدمات الرسمية ومواقع المصادر التي تحقّقت منها الإدارة. تحقّقي دائمًا من أنك على الموقع الرسمي قبل إدخال بياناتك." />

    @if ($links->isEmpty() && $sources->isEmpty())
        <x-empty-state class="mt-6" icon="link" title="لم تُضف روابط رسمية بعد" description="تُضاف الروابط هنا بعد أن تتحقق منها الإدارة." />
    @endif

    @if ($links->isNotEmpty())
        <section aria-labelledby="official-title" class="mt-6">
            <h2 id="official-title" class="text-lg font-semibold text-ink">خدمات رسمية</h2>
            <div class="mt-3 grid gap-3 md:grid-cols-2">
                @foreach ($links as $link)
                    <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer" class="card flex items-start gap-4 p-4 transition hover:border-brand-300 hover:shadow-md">
                        <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-700">
                            <x-icon name="link" class="h-5 w-5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex flex-wrap items-center gap-2">
                                <span class="font-heading font-semibold text-ink">{{ $link->title }}</span>
                                <x-badge color="success"><x-icon name="shield-check" class="h-3.5 w-3.5" /> مصدر رسمي</x-badge>
                            </span>
                            @if ($link->description)
                                <span class="mt-1 block text-sm text-muted">{{ $link->description }}</span>
                            @endif
                            <span class="mt-1 block truncate text-xs text-muted" dir="ltr">{{ parse_url($link->url, PHP_URL_HOST) }}</span>
                            <span class="sr-only">(يفتح في نافذة جديدة)</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($sources->isNotEmpty())
        <section aria-labelledby="sources-title" class="mt-8">
            <h2 id="sources-title" class="text-lg font-semibold text-ink">مواقع المصادر</h2>
            <p class="mt-1 text-sm text-muted">ذكر المصدر لا يعني شراكة رسمية معه.</p>
            <ul class="mt-3 grid gap-3 md:grid-cols-2">
                @foreach ($sources as $source)
                    <li>
                        <a href="{{ $source->website_url }}" target="_blank" rel="noopener noreferrer" class="card flex items-center justify-between gap-4 p-4 transition hover:border-brand-300">
                            <span class="font-heading font-semibold text-ink">{{ $source->name }}</span>
                            <span class="truncate text-xs text-muted" dir="ltr">{{ parse_url($source->website_url, PHP_URL_HOST) }}</span>
                            <span class="sr-only">(يفتح في نافذة جديدة)</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</x-public-layout>
