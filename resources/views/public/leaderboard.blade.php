<x-public-layout title="لوحة الشرف">
    <section class="relative overflow-hidden rounded-[2rem] bg-brand-gradient px-6 py-10 text-white shadow-card sm:px-12">
        <div aria-hidden="true" class="pointer-events-none absolute -start-16 -top-16 h-56 w-56 rounded-full bg-white/10"></div>
        <div class="relative flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="max-w-xl">
                <p class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1.5 text-sm font-bold"><x-icon name="trophy" class="h-5 w-5" /> لوحة الشرف</p>
                <h1 class="mt-4 text-3xl font-extrabold sm:text-4xl">أكثر الطالبات إنجازًا</h1>
                <p class="mt-3 leading-relaxed text-white/90">
                    {{ \App\Services\LeaderboardService::POINTS_PER_COMPLETION }} نقاط لكل درس أو لعبة أو اختبار تُكمله الطالبة،
                    و{{ \App\Services\LeaderboardService::POINTS_PER_CORRECT_ANSWER }} نقاط لكل إجابة صحيحة في «تحدي اليوم».
                </p>
            </div>
            <x-illustration name="supervision" icon="trophy" class="mx-auto aspect-square w-36 shrink-0 sm:mx-0" />
        </div>
    </section>

    <nav class="mt-6 inline-flex gap-1 rounded-xl bg-brand-50 p-1" aria-label="الفترة">
        @foreach ($periods as $key => $label)
            <a href="{{ route('leaderboard', ['period' => $key]) }}" @if ($period === $key) aria-current="page" @endif
                @class(['min-h-[40px] rounded-lg px-4 py-2 text-sm font-bold transition', 'bg-surface text-brand-800 shadow-sm' => $period === $key, 'text-muted hover:text-brand-700' => $period !== $key])>{{ $label }}</a>
        @endforeach
    </nav>

    @if ($rows === [])
        <x-empty-state class="mt-6" icon="trophy" title="لا توجد نقاط بعد في هذه الفترة"
            description="أكملي درسًا أو أجيبي عن تحدي اليوم لتكوني أول من يظهر هنا." />
    @else
        <ol class="mt-6 space-y-3">
            @foreach ($rows as $row)
                <li @class([
                    'card flex items-center gap-4 p-4 sm:p-5',
                    'border-amber-300 bg-amber-50/60' => $row['rank'] === 1,
                ])>
                    <span @class([
                        'inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full font-heading text-lg font-extrabold',
                        'bg-amber-400 text-white' => $row['rank'] === 1,
                        'bg-slate-300 text-white' => $row['rank'] === 2,
                        'bg-orange-300 text-white' => $row['rank'] === 3,
                        'bg-brand-100 text-brand-800' => $row['rank'] > 3,
                    ])>{{ $row['rank'] }}</span>
                    <span class="min-w-0 flex-1">
                        <span class="block font-heading font-bold text-ink">{{ $row['name'] }}</span>
                        <span class="text-sm text-muted">
                            {{ $row['grade'] ?? 'طالبة' }} · أكملت {{ $row['completed'] }} · {{ $row['correct'] }} إجابة صحيحة في التحدي
                        </span>
                    </span>
                    <span class="text-center">
                        <span class="block font-heading text-2xl font-extrabold text-brand-700">{{ number_format($row['points']) }}</span>
                        <span class="text-xs text-muted">نقطة</span>
                    </span>
                </li>
            @endforeach
        </ol>
    @endif

    <p class="mt-6 text-center text-xs text-muted">حفاظًا على الخصوصية تظهر الأسماء الأولى والحرف الأول من اسم العائلة فقط. تُحدَّث اللوحة كل 10 دقائق.</p>

    @guest
        @if ($registrationOpen)
            <div class="mt-8 text-center">
                <a href="{{ route('register') }}" class="btn-primary"><x-icon name="user-plus" /> انضمّي وابدئي جمع النقاط</a>
            </div>
        @endif
    @endguest
</x-public-layout>
