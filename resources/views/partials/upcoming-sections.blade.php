@php($upcoming = collect($navigation)->whereNull('route'))
@if ($upcoming->isNotEmpty())
{{-- Lists the sections planned for later phases. Non-interactive by design. --}}
<section aria-labelledby="upcoming-title" class="mt-8">
    <h2 id="upcoming-title" class="text-lg font-bold text-ink">الأقسام القادمة</h2>
    <p class="mt-1 text-sm text-muted">ستُفعَّل هذه الأقسام تباعًا في المراحل القادمة من تطوير المنصة.</p>

    <ul class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
        @foreach ($upcoming as $item)
            <li class="card flex flex-col items-start gap-3 p-4">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-brand-100 text-brand-700">
                    <x-icon :name="$item['icon']" class="h-6 w-6" />
                </span>
                <span class="font-heading text-sm font-bold text-ink">{{ $item['label'] }}</span>
                <x-badge color="gray">قريبًا</x-badge>
            </li>
        @endforeach
    </ul>
</section>
@endif
