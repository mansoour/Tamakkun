<x-app-layout area="admin" title="لوحة الإدارة">
    <h1 class="text-2xl font-bold text-ink">لوحة الإدارة</h1>
    <p class="mt-1 text-muted">مرحبًا {{ Auth::user()->name }}</p>

    <x-dev-notice class="mt-6">
        ستتيح هذه اللوحة قريبًا إدارة المدارس والفصول والمستخدمين والمحتوى والإعدادات وسجل التدقيق.
    </x-dev-notice>

    @include('partials.upcoming-sections', ['navigation' => \App\Support\Navigation::for('admin')])
</x-app-layout>
