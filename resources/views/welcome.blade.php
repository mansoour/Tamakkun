<x-public-layout>
    {{-- Hero --}}
    <section class="relative overflow-hidden rounded-[2rem] bg-brand-gradient px-6 py-12 text-white shadow-card sm:px-12 sm:py-16">
        <div aria-hidden="true" class="pointer-events-none absolute -start-24 -top-24 h-72 w-72 rounded-full bg-white/10"></div>
        <div aria-hidden="true" class="pointer-events-none absolute -bottom-32 end-1/3 h-80 w-80 rounded-full bg-white/5"></div>

        <div class="relative grid items-center gap-10 lg:grid-cols-[1.15fr_1fr]">
            <div>
                @if ($supervisorName)
                    <p class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1.5 text-sm font-bold backdrop-blur">
                        <x-icon name="check-badge" class="h-5 w-5" />
                        {{ $supervisorTitle }}: {{ $supervisorName }}
                    </p>
                @endif

                <h1 class="mt-5 text-3xl font-extrabold leading-snug sm:text-5xl sm:leading-tight">{{ $platformTagline ?: $platformName }}</h1>
                <p class="mt-4 text-lg font-medium text-white/90">استعداد • تدريب • متابعة • إنجاز</p>
                <p class="mt-5 max-w-xl leading-loose text-white/90">
                    {{ $platformName }} منصة عربية تجمع لطالبات المرحلة الثانوية كل ما يحتجنه للاستعداد لاختباري
                    <strong>القدرات العامة</strong> و<strong>التحصيلي</strong>: دروس مصوّرة، وألعاب تدريبية، واختبارات إلكترونية،
                    وتجميعات محلولة، في مسار مرتّب من التأسيس حتى المراجعة، مع متابعة مستمرة من الموجهة الطلابية.
                </p>

                @guest
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                        @if ($registrationOpen)
                            <a href="{{ route('register') }}" class="btn bg-white text-brand-800 hover:bg-brand-50">
                                <x-icon name="user-plus" /> إنشاء حساب طالبة
                            </a>
                        @endif
                        <a href="{{ route('login') }}" class="btn border border-white/60 text-white hover:bg-white/10">دخول الطالبة</a>
                        <a href="{{ route('login', ['as' => 'counselor']) }}" class="btn border border-white/30 text-white/90 hover:bg-white/10">دخول الموجهة الطلابية</a>
                    </div>
                @else
                    <a href="{{ route('dashboard') }}" class="btn mt-8 bg-white text-brand-800 hover:bg-brand-50">الذهاب إلى لوحتي</a>
                @endguest
            </div>

            <x-illustration name="hero" eager icon="academic-cap" class="mx-auto aspect-[6/5] w-full max-w-md lg:max-w-none" />
        </div>
    </section>

    {{-- Live numbers --}}
    @if ($stats['total'] > 0)
        <section class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-4" aria-label="المنصة بالأرقام">
            @foreach ([
                ['value' => $stats['videos'], 'label' => 'درس ومقطع مصوّر', 'icon' => 'play-circle'],
                ['value' => $stats['practice'], 'label' => 'لعبة واختبار تفاعلي', 'icon' => 'puzzle-piece'],
                ['value' => $stats['files'], 'label' => 'ملف ومرجع', 'icon' => 'document-text'],
                ['value' => $stats['chapters'], 'label' => 'فصلًا في التحصيلي', 'icon' => 'book-open'],
            ] as $stat)
                <div class="card flex items-center gap-3 p-4 sm:p-5">
                    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-700">
                        <x-icon :name="$stat['icon']" class="h-6 w-6" />
                    </span>
                    <span>
                        <span class="block font-heading text-2xl font-extrabold text-ink">{{ number_format($stat['value']) }}</span>
                        <span class="text-sm text-muted">{{ $stat['label'] }}</span>
                    </span>
                </div>
            @endforeach
        </section>
    @endif

    {{-- Learning paths --}}
    <section class="mt-16" aria-labelledby="paths-title">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-sm font-bold text-brand-700">ماذا تجدين في المنصة؟</p>
            <h2 id="paths-title" class="mt-2 text-2xl font-extrabold text-ink sm:text-3xl">ثلاثة مسارات تقودك إلى درجتك المستهدفة</h2>
            <p class="mt-3 leading-relaxed text-muted">كل مسار مقسّم حسب المهارة أو الفصل الدراسي، ومرتّب بحيث تبدئين بالشرح ثم التدريب ثم الاختبار.</p>
        </div>

        <div class="mt-8 grid gap-5 md:grid-cols-3">
            @foreach ([
                ['image' => 'path-quantitative', 'icon' => 'calculator', 'title' => 'القدرات – الكمي', 'section' => 'quantitative',
                    'text' => 'استراتيجيات الحل، والهندسة، والجبر، والإحصاء، وأسئلة المقارنة، مع نماذج وتجميعات محلولة بالفيديو.'],
                ['image' => 'path-verbal', 'icon' => 'book-open', 'title' => 'القدرات – اللفظي', 'section' => 'verbal',
                    'text' => 'التناظر اللفظي، وإكمال الجمل، والخطأ السياقي، والمفردة الشاذة، واستيعاب المقروء بألعاب واختبارات قصيرة.'],
                ['image' => 'path-tahsili', 'icon' => 'beaker', 'title' => 'التحصيلي', 'section' => 'tahsili',
                    'text' => 'فصول رياضيات الصفوف الثلاثة، لكل درس لعبة وحلّها، مع دورة «فن التحصيلي» وتجميعات 1446 و1447.'],
            ] as $path)
                <article class="card overflow-hidden">
                    <x-illustration :name="$path['image']" :icon="$path['icon']" class="aspect-[4/3] w-full rounded-none" />
                    <div class="p-6">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-lg font-bold text-ink">{{ $path['title'] }}</h3>
                            @if ($count = $stats['sections'][$path['section']] ?? 0)
                                <span class="rounded-full bg-brand-100 px-3 py-1 text-xs font-bold text-brand-800">{{ number_format($count) }} عنصر</span>
                            @endif
                        </div>
                        <p class="mt-2 text-sm leading-relaxed text-muted">{{ $path['text'] }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    {{-- How it works --}}
    <section class="mt-16 rounded-[2rem] bg-surface px-6 py-12 shadow-card sm:px-12" aria-labelledby="steps-title">
        <div class="grid items-center gap-10 lg:grid-cols-[1fr_1.2fr]">
            <x-illustration name="how-it-works" icon="rocket-launch" class="mx-auto aspect-square w-full max-w-sm" />
            <div>
                <p class="text-sm font-bold text-brand-700">كيف تبدئين؟</p>
                <h2 id="steps-title" class="mt-2 text-2xl font-extrabold text-ink sm:text-3xl">أربع خطوات بسيطة</h2>
                <ol class="mt-6 space-y-5">
                    @foreach ([
                        ['title' => 'أنشئي حسابك', 'text' => 'سجّلي باسمك واختاري مدرستك وصفّك وفصلك، أو ادخلي بالحساب الذي أنشأته المدرسة.'],
                        ['title' => 'تعلّمي بالترتيب', 'text' => 'ابدئي بمرحلة التأسيس (الشرح)، ثم التدريب، ثم الإتقان، ثم المراجعة.'],
                        ['title' => 'تدرّبي واختبري نفسك', 'text' => 'ألعاب تفاعلية واختبارات إلكترونية، ولكل نموذج فيديو يشرح الحل خطوة بخطوة.'],
                        ['title' => 'تابعي تقدّمك وموعدك', 'text' => 'سجّلي موعد اختبارك ودرجتك المستهدفة، وشاهدي نسبة إنجازك وأوسمتك.'],
                    ] as $i => $step)
                        <li class="flex gap-4">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-gradient font-heading font-bold text-white">{{ $i + 1 }}</span>
                            <div>
                                <h3 class="font-bold text-ink">{{ $step['title'] }}</h3>
                                <p class="mt-1 text-sm leading-relaxed text-muted">{{ $step['text'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="mt-16" aria-labelledby="features-title">
        <h2 id="features-title" class="text-center text-2xl font-extrabold text-ink sm:text-3xl">كل ما تحتاجينه في مكان واحد</h2>
        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ([
                ['icon' => 'play-circle', 'title' => 'دروس مصوّرة', 'text' => 'شرح مختصر لكل مهارة وكل درس، يعمل داخل المنصة دون الخروج منها.'],
                ['icon' => 'puzzle-piece', 'title' => 'ألعاب تدريبية', 'text' => 'تدريب ممتع على Wordwall وQuizalize يثبّت المهارة قبل الاختبار.'],
                ['icon' => 'clipboard-document-list', 'title' => 'اختبارات إلكترونية', 'text' => 'اختبارات قصيرة وشاملة ومحاكية لقياس مستواك قبل يوم الاختبار.'],
                ['icon' => 'calendar-days', 'title' => 'موعدي ودرجتي', 'text' => 'مواعيد الاختبارات والدرجات والهدف المطلوب في مكان واحد.'],
                ['icon' => 'chart-bar', 'title' => 'متابعة التقدّم', 'text' => 'نسبة إنجازك في كل قسم، وما أكملتِه وما بقي عليك.'],
                ['icon' => 'trophy', 'title' => 'تحدي اليوم والأوسمة', 'text' => 'سؤال يومي ودفعة تحفيزية وأوسمة تشجعك على الاستمرار.'],
            ] as $feature)
                <div class="card p-6">
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-brand-100 text-brand-700">
                        <x-icon :name="$feature['icon']" class="h-6 w-6" />
                    </span>
                    <h3 class="mt-4 text-lg font-bold text-ink">{{ $feature['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-muted">{{ $feature['text'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Counselor + supervision --}}
    <section class="mt-16 grid gap-5 lg:grid-cols-2">
        <div class="card flex flex-col gap-5 p-6 sm:flex-row sm:items-center sm:p-8">
            <x-illustration name="counselor" icon="users" class="aspect-square w-32 shrink-0" />
            <div>
                <h2 class="text-xl font-extrabold text-ink">للموجهة الطلابية</h2>
                <p class="mt-2 text-sm leading-relaxed text-muted">
                    لوحة تعرض طالباتك ونشاطهن ودرجاتهن، وتنبيهات ذكية بمن تحتاج متابعة، وإعلانات وتقارير PDF وCSV جاهزة.
                </p>
            </div>
        </div>

        @if ($supervisorName)
            <div class="relative overflow-hidden rounded-card bg-brand-900 p-6 text-white shadow-card sm:p-8">
                <div aria-hidden="true" class="pointer-events-none absolute -end-16 -top-16 h-48 w-48 rounded-full bg-white/5"></div>
                <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center">
                    <x-illustration name="supervision" icon="check-badge" class="aspect-square w-32 shrink-0" />
                    <div>
                        <p class="text-sm font-bold text-brand-300">{{ $supervisorTitle }}</p>
                        <h2 class="mt-1 text-2xl font-extrabold">{{ $supervisorName }}</h2>
                        <p class="mt-2 text-sm leading-relaxed text-white/80">
                            تُدار المنصة ومحتواها بإشراف {{ $supervisorName }}، وتُختار الدروس والألعاب والاختبارات لتناسب احتياج الطالبات واستعدادهن.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </section>

    {{-- Final call to action --}}
    @guest
        <section class="mt-16 overflow-hidden rounded-[2rem] bg-brand-gradient px-6 py-10 text-center text-white shadow-card sm:px-12">
            <h2 class="text-2xl font-extrabold sm:text-3xl">ابدئي اليوم… فخطوتك اليوم تصنع نتيجتك غدًا</h2>
            <p class="mx-auto mt-3 max-w-xl text-white/90">أنشئي حسابك في أقل من دقيقة، واختاري مدرستك وصفّك وفصلك.</p>
            <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                @if ($registrationOpen)
                    <a href="{{ route('register') }}" class="btn bg-white text-brand-800 hover:bg-brand-50"><x-icon name="user-plus" /> إنشاء حساب طالبة</a>
                @endif
                <a href="{{ route('login') }}" class="btn border border-white/60 text-white hover:bg-white/10">لديّ حساب</a>
            </div>
        </section>
    @endguest
</x-public-layout>
