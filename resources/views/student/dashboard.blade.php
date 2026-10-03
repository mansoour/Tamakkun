<x-app-layout area="student" title="الرئيسية">
    <section class="overflow-hidden rounded-card bg-brand-gradient p-6 text-white shadow-card sm:p-8">
        <p class="text-sm text-white/85">مرحبًا بكِ</p>
        <h1 class="mt-1 text-2xl font-extrabold sm:text-3xl">{{ Auth::user()->name }}</h1>
        <p class="mt-3 max-w-xl text-white/90">
            @if ($summary['completion']['total'] > 0)
                أنجزتِ {{ $summary['completion']['percentage'] }}% من المحتوى المتاح · هذا الأسبوع: {{ $summary['weekly']['completed'] }}/{{ $summary['weekly']['goal'] }}
            @else
                خطوتك اليوم… تصنع نتيجتك غدًا
            @endif
        </p>
    </section>

    <div class="mt-6 grid gap-4 lg:grid-cols-3">
        <x-exam-countdown :next="$nextExam" editable />
        @foreach ($exams as $exam)
            <x-score-summary :exam="$exam" />
        @endforeach
    </div>

    <x-student-progress-card :summary="$summary" class="mt-6" />

    @if ($announcements->isNotEmpty())
        <section id="announcements" class="mt-6" aria-labelledby="announcements-title">
            <h2 id="announcements-title" class="text-lg font-bold text-ink">إعلانات</h2>
            <div class="mt-3 space-y-3">
                @foreach ($announcements as $announcement)
                    <article class="card border-s-4 border-s-brand-600 p-4">
                        <h3 class="font-heading font-bold text-ink">{{ $announcement->title }}</h3>
                        <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-ink">{{ $announcement->body }}</p>
                        <p class="mt-2 text-xs text-muted">{{ $announcement->author?->name }} · <span dir="ltr">{{ ($announcement->starts_at ?? $announcement->created_at)->format('Y-m-d') }}</span></p>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <a href="{{ route('student.challenge') }}" class="card flex items-center gap-4 p-5 transition hover:border-brand-300 hover:shadow-md">
            <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-brand-gradient text-white"><x-icon name="bolt" class="h-6 w-6" /></span>
            <span>
                <span class="block font-heading font-bold text-ink">تحدي اليوم</span>
                <span class="text-sm text-muted">
                    @if (! $challenge) لا يوجد تحدٍّ اليوم
                    @elseif ($challengeAnswered >= $challenge->questions->count()) أكملتِ تحدي اليوم
                    @else {{ $challenge->questions->count() - $challengeAnswered }} سؤال بانتظارك
                    @endif
                </span>
            </span>
        </a>
        <a href="{{ route('student.motivation') }}" class="card flex items-center gap-4 p-5 transition hover:border-brand-300 hover:shadow-md">
            <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-brand-gradient text-white"><x-icon name="sparkles" class="h-6 w-6" /></span>
            <span class="min-w-0">
                <span class="block font-heading font-bold text-ink">دفعة اليوم</span>
                <span class="line-clamp-1 text-sm text-muted">{{ $motivation?->title ?? 'لا توجد دفعة اليوم بعد' }}</span>
            </span>
        </a>
    </div>

    @if ($sharedNotes->isNotEmpty())
        <section class="mt-6" aria-labelledby="notes-title">
            <h2 id="notes-title" class="text-lg font-bold text-ink">رسائل من الموجهة الطلابية</h2>
            <div class="mt-3 space-y-3">
                @foreach ($sharedNotes as $note)
                    <article class="card p-4">
                        <p class="whitespace-pre-line text-sm leading-relaxed text-ink">{{ $note->note }}</p>
                        <p class="mt-2 text-xs text-muted">{{ $note->counselor?->name }} · <span dir="ltr">{{ $note->created_at->format('Y-m-d') }}</span></p>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if ($continue)
        <section class="mt-6" aria-labelledby="continue-title">
            <h2 id="continue-title" class="text-lg font-bold text-ink">أكملي من حيث توقفتِ</h2>
            <x-content-card :content="$continue" :status="\App\Enums\ProgressStatus::IN_PROGRESS" class="mt-3" />
        </section>
    @endif

    <section class="mt-8" aria-labelledby="learn-title">
        <h2 id="learn-title" class="text-lg font-bold text-ink">أقسام المنصة</h2>
        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            @foreach (collect(\App\Support\Navigation::for('student'))->filter(fn ($i) => $i['route'] && ! in_array($i['route'], ['student.dashboard', 'profile.edit'])) as $item)
                <a href="{{ route($item['route']) }}" class="card flex flex-col items-start gap-3 p-4 transition hover:border-brand-300 hover:shadow-md">
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-brand-gradient text-white">
                        <x-icon :name="$item['icon']" class="h-6 w-6" />
                    </span>
                    <span class="font-heading text-sm font-bold text-ink">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
    </section>

</x-app-layout>
