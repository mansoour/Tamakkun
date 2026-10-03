<x-app-layout area="counselor" title="لوحة الموجهة الطلابية">
    <h1 class="text-2xl font-bold text-ink">لوحة الموجهة الطلابية</h1>
    <p class="mt-1 text-muted">مرحبًا {{ Auth::user()->name }}</p>

    <x-dev-notice class="mt-6">
        ستعرض هذه اللوحة قريبًا مؤشرات الطالبات المسندات إليكِ: نسبة الإنجاز، ومن تحتاج متابعة، والاختبارات القادمة، ومن لم يحجزن.
    </x-dev-notice>

    @include('partials.upcoming-sections', ['navigation' => \App\Support\Navigation::for('counselor')])
</x-app-layout>
