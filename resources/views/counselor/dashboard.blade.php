<x-app-layout area="counselor" title="لوحة الموجهة الطلابية">
    <h1 class="text-2xl font-bold text-ink">لوحة الموجهة الطلابية</h1>
    <p class="mt-1 text-muted">مرحبًا {{ Auth::user()->name }}</p>

    <section class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4" aria-label="مؤشرات">
        <x-stat-card label="عدد الطالبات" :value="$kpis['students']" icon="users" />
        <x-stat-card label="متوسط الإنجاز" :value="$kpis['average_completion'].'%'" icon="chart-bar" />
        <x-stat-card label="تحتاج متابعة" :value="$kpis['needs_follow_up']" icon="flag" />
        <x-stat-card label="الاختبارات القادمة" :value="$kpis['upcoming_exams']" icon="calendar-days" />
        <x-stat-card label="متوسط التحسن" :value="$kpis['average_improvement'] === null ? '—' : ($kpis['average_improvement'] > 0 ? '+' : '').$kpis['average_improvement']" icon="arrow-trending-up" />
        <x-stat-card label="لم يحجزن" :value="$kpis['not_booked']" icon="exclamation-triangle" />
        <x-stat-card label="غير نشطات" :value="$kpis['inactive']" icon="clock" />
    </section>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section aria-labelledby="attention-title">
            <div class="flex items-center justify-between">
                <h2 id="attention-title" class="text-lg font-semibold text-ink">تحتاج متابعة</h2>
                <a href="{{ route('counselor.follow-up') }}" class="btn-ghost min-h-[40px] px-3">عرض الكل</a>
            </div>
            @if ($attention->isEmpty())
                <x-empty-state class="mt-3" icon="check-circle" title="لا توجد طالبات بحاجة لمتابعة الآن" />
            @else
                <ul class="card mt-3 divide-y divide-line">
                    @foreach ($attention as $row)
                        <li class="flex items-center justify-between gap-3 p-4">
                            <a href="{{ route('counselor.students.show', $row['profile']) }}" class="font-medium text-brand-700 underline-offset-4 hover:underline">{{ $row['name'] }}</a>
                            <span class="flex flex-wrap justify-end gap-1">
                                @if ($row['open_alerts']) <x-badge color="danger">{{ $row['open_alerts'] }} تنبيه</x-badge> @endif
                                <x-student-status-badge :status="$row['follow_up']" />
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section aria-labelledby="upcoming-title">
            <div class="flex items-center justify-between">
                <h2 id="upcoming-title" class="text-lg font-semibold text-ink">الاختبارات القادمة</h2>
                <a href="{{ route('counselor.exams') }}" class="btn-ghost min-h-[40px] px-3">عرض الكل</a>
            </div>
            @if ($upcoming->isEmpty())
                <x-empty-state class="mt-3" icon="calendar-days" title="لا توجد اختبارات محجوزة قادمة" />
            @else
                <ul class="card mt-3 divide-y divide-line">
                    @foreach ($upcoming as $row)
                        <li class="flex items-center justify-between gap-3 p-4">
                            <a href="{{ route('counselor.students.show', $row['profile']) }}" class="font-medium text-brand-700 underline-offset-4 hover:underline">{{ $row['name'] }}</a>
                            <span class="text-sm text-muted">{{ $row['next_exam']['attempt']->exam_type->label() }} · {{ \App\Support\ArabicDays::until($row['next_exam']['days']) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    <section class="mt-8" aria-labelledby="alerts-title">
        <div class="flex items-center justify-between">
            <h2 id="alerts-title" class="text-lg font-semibold text-ink">أحدث التنبيهات</h2>
            <a href="{{ route('counselor.alerts') }}" class="btn-ghost min-h-[40px] px-3">كل التنبيهات</a>
        </div>
        <div class="mt-3 space-y-3">
            @forelse ($alerts as $alert)
                <x-follow-up-alert :alert="$alert" show-student />
            @empty
                <x-empty-state icon="bell" title="لا توجد تنبيهات مفتوحة" />
            @endforelse
        </div>
    </section>
</x-app-layout>
