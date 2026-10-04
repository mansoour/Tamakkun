<x-public-layout title="المحتوى التعليمي">
    <x-page-header title="المحتوى التعليمي" description="كل ما في المنصة من دروس مصوّرة وألعاب تدريبية واختبارات إلكترونية ومراجع، مرتّبًا حسب القسم والمهارة." />

    @include('public.content._join')

    <div class="mt-6 grid gap-5 md:grid-cols-3">
        @foreach ($sections as $item)
            <a href="{{ route('browse.section', $item['section']->value) }}" class="card group flex flex-col overflow-hidden transition hover:border-brand-300 hover:shadow-md">
                <x-illustration :name="'path-'.$item['section']->value" :icon="$item['section']->icon()" class="aspect-[4/3] w-full rounded-none" />
                <span class="flex flex-1 flex-col p-6">
                    <span class="flex items-center justify-between gap-3">
                        <span class="font-heading text-lg font-bold text-ink group-hover:text-brand-800">{{ $item['section']->label() }}</span>
                        <span class="rounded-full bg-brand-100 px-3 py-1 text-xs font-bold text-brand-800">{{ number_format($item['count']) }} عنصر</span>
                    </span>
                    <span class="mt-3 flex flex-wrap gap-1.5">
                        @foreach ($item['groups']->take(8) as $group)
                            <x-badge color="gray">{{ $group }}</x-badge>
                        @endforeach
                        @if ($item['groups']->count() > 8)
                            <x-badge color="gray">+{{ $item['groups']->count() - 8 }}</x-badge>
                        @endif
                    </span>
                    <span class="mt-auto pt-4 text-sm font-bold text-brand-700">تصفّحي القسم ←</span>
                </span>
            </a>
        @endforeach
    </div>
</x-public-layout>
