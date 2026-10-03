<x-app-layout area="student" title="روابط مهمة">
    <x-page-header title="روابط مهمة" description="روابط التسجيل والخدمات والمصادر. تحقّقي دائمًا من أنك على الموقع الرسمي قبل إدخال بياناتك." />

    @if ($groups->isEmpty())
        <x-empty-state class="mt-6" icon="link" title="لم تُضف روابط بعد" description="ستضيف الإدارة الروابط الرسمية بعد التحقق منها." />
    @else
        <div class="mt-6 space-y-8">
            @foreach ($groups as $group)
                <section aria-labelledby="links-{{ $group['category']->value }}">
                    <h2 id="links-{{ $group['category']->value }}" class="text-lg font-semibold text-ink">{{ $group['category']->label() }}</h2>
                    <div class="mt-3 grid gap-3 md:grid-cols-2">
                        @foreach ($group['links'] as $link)
                            <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer" class="card flex items-start gap-4 p-4 transition hover:border-brand-300 hover:shadow-md">
                                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-700">
                                    <x-icon name="link" class="h-5 w-5" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex flex-wrap items-center gap-2">
                                        <span class="font-heading font-semibold text-ink">{{ $link->title }}</span>
                                        @if ($link->is_official)
                                            <x-badge color="success"><x-icon name="shield-check" class="h-3.5 w-3.5" /> مصدر رسمي</x-badge>
                                        @endif
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
            @endforeach
        </div>
    @endif
</x-app-layout>
