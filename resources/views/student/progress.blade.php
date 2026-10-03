<x-app-layout area="student" title="تقدمي">
    <x-page-header title="تقدمي" description="تُحسب النسب من المحتوى المنشور الذي سجّلتِ إنجازه بزر «أنجزت»." />

    <x-student-progress-card :summary="$summary" class="mt-6" />

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="card space-y-5 p-6" aria-labelledby="sections-title">
            <h2 id="sections-title" class="text-lg font-semibold text-ink">الإنجاز حسب القسم</h2>
            @foreach ($summary['sections'] as $section)
                <div>
                    <x-progress-bar :label="$section['label']" :percentage="$section['percentage']" />
                    <p class="mt-1 text-xs text-muted">{{ $section['completed'] }} من {{ $section['total'] }}</p>
                </div>
            @endforeach
            <div class="border-t border-line pt-5">
                <x-progress-bar label="هدف هذا الأسبوع" :percentage="$summary['weekly']['percentage']" />
                <p class="mt-1 text-xs text-muted">
                    {{ $summary['weekly']['completed'] }} من {{ $summary['weekly']['goal'] }}
                    @if ($summary['last_activity_at'])
                        · آخر نشاط: <span dir="ltr">{{ $summary['last_activity_at']->format('Y-m-d') }}</span>
                    @endif
                </p>
            </div>
        </section>

        <section class="space-y-3" aria-labelledby="continue-title">
            <h2 id="continue-title" class="text-lg font-semibold text-ink">أكملي من حيث توقفتِ</h2>
            @forelse ($inProgress as $content)
                <x-content-card :content="$content" :status="\App\Enums\ProgressStatus::IN_PROGRESS" />
            @empty
                <x-empty-state icon="play-circle" title="لا يوجد محتوى قيد التقدم" description="اضغطي «ابدأ» في أي درس لتجديه هنا." />
            @endforelse
        </section>
    </div>

    <section class="mt-8" aria-labelledby="completed-title">
        <h2 id="completed-title" class="text-lg font-semibold text-ink">آخر ما أنجزتِ</h2>
        @if ($completed->isEmpty())
            <x-empty-state class="mt-3" icon="check-circle" title="لم تُسجّلي أي إنجاز بعد" description="اضغطي «أنجزت» بعد إنهاء الدرس أو المقطع." />
        @else
            <div class="mt-3 grid gap-3 md:grid-cols-2">
                @foreach ($completed as $content)
                    <x-content-card :content="$content" :status="\App\Enums\ProgressStatus::COMPLETED" />
                @endforeach
            </div>
        @endif
    </section>
    @if ($badges !== null)
        <section class="mt-8" aria-labelledby="badges-title">
            <h2 id="badges-title" class="text-lg font-semibold text-ink">أوسمتي</h2>
            <p class="mt-1 text-sm text-muted">أوسمة شخصية تظهر لكِ فقط.</p>
            <ul class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                @foreach ($badges as $badge)
                    <li @class(['card flex flex-col items-center p-4 text-center', 'border-dashed bg-transparent shadow-none' => ! $badge['earned']])>
                        <span @class(['inline-flex h-12 w-12 items-center justify-center rounded-full', 'bg-brand-gradient text-white' => $badge['earned'], 'bg-gray-100 text-muted' => ! $badge['earned']])>
                            <x-icon :name="$badge['icon']" class="h-6 w-6" />
                        </span>
                        <span class="mt-2 font-heading text-sm font-semibold text-ink">{{ $badge['label'] }}</span>
                        <span class="mt-1 text-xs text-muted">{{ $badge['description'] }}</span>
                        @if ($badge['earned'])
                            <span class="sr-only">تم الحصول عليه</span>
                        @else
                            <span class="mt-2 text-xs font-medium text-muted">لم يتحقق بعد</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</x-app-layout>
