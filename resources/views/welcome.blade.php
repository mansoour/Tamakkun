<x-public-layout>
    <section class="overflow-hidden rounded-[2rem] bg-brand-gradient px-6 py-14 text-white shadow-card sm:px-12 sm:py-20">
        <div class="max-w-2xl">
            @if ($platformTagline)
                <h1 class="text-3xl font-bold leading-snug sm:text-5xl sm:leading-tight">{{ $platformTagline }}</h1>
            @else
                <h1 class="text-3xl font-bold leading-snug sm:text-5xl sm:leading-tight">{{ $platformName }}</h1>
            @endif
            <p class="mt-4 text-lg text-white/90">استعداد • تدريب • متابعة • إنجاز</p>
            <p class="mt-6 max-w-xl leading-relaxed text-white/90">
                {{ $platformName }} منصة تساعد طالبات الصف الثالث الثانوي على الاستعداد لاختباري القدرات العامة والتحصيلي،
                وتساعد الموجهة الطلابية على متابعة تقدّمهن.
            </p>

            @guest
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('login') }}" class="btn bg-white text-brand-800 hover:bg-brand-50">دخول الطالبة</a>
                    <a href="{{ route('login', ['as' => 'counselor']) }}" class="btn border border-white/60 text-white hover:bg-white/10">دخول الموجهة الطلابية</a>
                </div>
            @endguest
        </div>
    </section>

    <section class="mt-10 grid gap-4 sm:grid-cols-3" aria-label="ما تقدمه المنصة">
        @foreach ([
            ['icon' => 'book-open', 'title' => 'محتوى منظّم', 'text' => 'مسارات للقدرات الكمي واللفظي والتحصيلي مرتّبة من التأسيس إلى المراجعة.'],
            ['icon' => 'calendar-days', 'title' => 'موعدي ودرجتي', 'text' => 'متابعة مواعيد الاختبارات والدرجات والهدف المطلوب في مكان واحد.'],
            ['icon' => 'chart-bar', 'title' => 'متابعة التقدّم', 'text' => 'تعرف الطالبة مستوى إنجازها، وتعرف الموجهة من تحتاج متابعة.'],
        ] as $feature)
            <div class="card p-6">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-brand-100 text-brand-700">
                    <x-icon :name="$feature['icon']" class="h-6 w-6" />
                </span>
                <h2 class="mt-4 text-lg font-semibold text-ink">{{ $feature['title'] }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-muted">{{ $feature['text'] }}</p>
            </div>
        @endforeach
    </section>
</x-public-layout>
