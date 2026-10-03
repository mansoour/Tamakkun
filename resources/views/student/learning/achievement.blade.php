<x-app-layout area="student" title="التحصيلي">
    <x-page-header title="التحصيلي" description="اختاري المادة لعرض أبوابها وموضوعاتها." />

    @if ($subjects->isEmpty())
        <x-empty-state class="mt-6" icon="beaker" title="لا توجد مواد بعد" />
    @else
        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            @foreach ($subjects as $subject)
                <a href="{{ route('student.achievement.subject', $subject) }}" class="card flex items-center gap-4 p-5 transition hover:border-brand-300 hover:shadow-md">
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-100 text-brand-700">
                        <x-icon name="beaker" class="h-6 w-6" />
                    </span>
                    <span class="flex-1">
                        <span class="block font-heading font-semibold text-ink">{{ $subject->name }}</span>
                        <span class="text-sm text-muted">{{ $subject->contents_count }} عنصر منشور</span>
                    </span>
                    <x-icon name="chevron-left" class="h-5 w-5 text-muted" />
                </a>
            @endforeach
        </div>
    @endif
</x-app-layout>
