<x-app-layout area="admin" title="لوحة الإدارة">
    <h1 class="text-2xl font-bold text-ink">لوحة الإدارة</h1>
    <p class="mt-1 text-muted">مرحبًا {{ Auth::user()->name }}</p>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3" aria-label="ملخص">
        <x-stat-card label="المدارس" :value="$metrics['schools']" icon="building-library" />
        <x-stat-card label="الفصول" :value="$metrics['classrooms']" icon="squares-2x2" />
        <x-stat-card label="الطالبات" :value="$metrics['students']" icon="users" />
        <x-stat-card label="الموجهات" :value="$metrics['counselors']" icon="user-circle" />
        <x-stat-card label="حسابات بانتظار التفعيل" :value="$metrics['pending']" icon="clock" />
        <x-stat-card label="طالبات بدون موجهة" :value="$metrics['unassigned']" icon="flag" />
    </section>

    @include('partials.upcoming-sections', ['navigation' => \App\Support\Navigation::for('admin')])
</x-app-layout>
