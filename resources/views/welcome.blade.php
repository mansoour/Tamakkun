<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        @include('layouts.partials.head')
    </head>
    <body class="min-h-screen">
        <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:start-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">تخطي إلى المحتوى</a>

        <header class="mx-auto flex max-w-6xl items-center justify-between px-4 py-5 sm:px-6">
            <x-brand />
            @auth
                <a href="{{ route('dashboard') }}" class="btn-primary">لوحتي</a>
            @else
                <a href="{{ route('login') }}" class="btn-secondary">تسجيل الدخول</a>
            @endauth
        </header>

        <main id="main" class="mx-auto max-w-6xl px-4 pb-16 sm:px-6">
            <section class="overflow-hidden rounded-[2rem] bg-brand-gradient px-6 py-14 text-white shadow-card sm:px-12 sm:py-20">
                <div class="max-w-2xl">
                    <h1 class="text-3xl font-bold leading-snug sm:text-5xl sm:leading-tight">خطوتك اليوم… تصنع نتيجتك غدًا</h1>
                    <p class="mt-4 text-lg text-white/90">استعداد • تدريب • متابعة • إنجاز</p>
                    <p class="mt-6 max-w-xl leading-relaxed text-white/90">
                        تمكّن منصة تساعد طالبات الصف الثالث الثانوي على الاستعداد لاختباري القدرات العامة والتحصيلي،
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
        </main>

        <footer class="border-t border-line py-6 text-center text-sm text-muted">
            تمكّن © {{ now()->year }}
        </footer>
    </body>
</html>
