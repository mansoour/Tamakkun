<x-app-layout area="student" title="الرئيسية">
    <section class="overflow-hidden rounded-card bg-brand-gradient p-6 text-white shadow-card sm:p-8">
        <p class="text-sm text-white/85">مرحبًا بكِ</p>
        <h1 class="mt-1 text-2xl font-bold sm:text-3xl">{{ Auth::user()->name }}</h1>
        <p class="mt-3 max-w-xl text-white/90">خطوتك اليوم… تصنع نتيجتك غدًا</p>
    </section>

    <x-dev-notice class="mt-6">
        هذه لوحة الطالبة في نسختها الأولية. ستظهر هنا قريبًا مواعيد اختباراتك ودرجاتك ونسبة إنجازك الفعلية.
    </x-dev-notice>

    @include('partials.upcoming-sections', ['navigation' => \App\Support\Navigation::for('student')])
</x-app-layout>
