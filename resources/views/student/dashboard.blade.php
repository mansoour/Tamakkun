<x-app-layout area="student" title="الرئيسية">
    <section class="overflow-hidden rounded-card bg-brand-gradient p-6 text-white shadow-card sm:p-8">
        <p class="text-sm text-white/85">مرحبًا بكِ</p>
        <h1 class="mt-1 text-2xl font-bold sm:text-3xl">{{ Auth::user()->name }}</h1>
        <p class="mt-3 max-w-xl text-white/90">خطوتك اليوم… تصنع نتيجتك غدًا</p>
    </section>

    <section class="mt-8" aria-labelledby="learn-title">
        <h2 id="learn-title" class="text-lg font-semibold text-ink">ابدئي التعلّم</h2>
        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            @foreach (collect(\App\Support\Navigation::for('student'))->filter(fn ($i) => $i['route'] && ! in_array($i['route'], ['student.dashboard', 'profile.edit'])) as $item)
                <a href="{{ route($item['route']) }}" class="card flex flex-col items-start gap-3 p-4 transition hover:border-brand-300 hover:shadow-md">
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-brand-gradient text-white">
                        <x-icon :name="$item['icon']" class="h-6 w-6" />
                    </span>
                    <span class="font-heading text-sm font-semibold text-ink">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
    </section>

    <x-dev-notice class="mt-8">
        ستظهر هنا قريبًا مواعيد اختباراتك ودرجاتك ونسبة إنجازك الفعلية.
    </x-dev-notice>

    @include('partials.upcoming-sections', ['navigation' => \App\Support\Navigation::for('student')])
</x-app-layout>
