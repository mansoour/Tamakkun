<x-app-layout area="counselor" title="لوحة الموجهة الطلابية">
    <h1 class="text-2xl font-bold text-ink">لوحة الموجهة الطلابية</h1>
    <p class="mt-1 text-muted">مرحبًا {{ Auth::user()->name }}</p>

    <section class="mt-6 grid gap-4 sm:grid-cols-2" aria-label="ملخص">
        <x-stat-card label="عدد الطالبات" :value="$metrics['students']" icon="users" />
        <x-stat-card label="حسابات نشطة" :value="$metrics['active']" icon="check-circle" />
    </section>

    <x-dev-notice class="mt-6">
        ستُضاف قريبًا مؤشرات نسبة الإنجاز، ومن تحتاج متابعة، والاختبارات القادمة، ومن لم يحجزن.
    </x-dev-notice>

    @include('partials.upcoming-sections', ['navigation' => \App\Support\Navigation::for('counselor')])
</x-app-layout>
