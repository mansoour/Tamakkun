<x-app-layout :area="$area" title="التقارير">
    <x-page-header title="التقارير" description="{{ $area === 'admin' ? 'تشمل جميع الطالبات.' : 'تشمل طالباتك المسندات فقط.' }} يمكن تصدير كل تقرير بصيغة PDF أو CSV." />

    <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($reports as $key => [$title, $description])
            <a href="{{ route($area.'.reports.show', $key) }}" class="card flex items-start gap-4 p-5 transition hover:border-brand-300 hover:shadow-md">
                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-700"><x-icon name="document-text" class="h-6 w-6" /></span>
                <span>
                    <span class="block font-heading font-semibold text-ink">{{ $title }}</span>
                    <span class="mt-1 block text-sm text-muted">{{ $description }}</span>
                </span>
            </a>
        @endforeach
    </div>
</x-app-layout>
