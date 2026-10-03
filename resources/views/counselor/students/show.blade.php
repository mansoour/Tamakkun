@php
    $tabs = [
        'overview' => 'نظرة عامة', 'scores' => 'الدرجات', 'exams' => 'الاختبارات', 'activity' => 'الأنشطة',
        'content' => 'المحتوى', 'challenges' => 'التحديات', 'notes' => 'الملاحظات', 'alerts' => 'التنبيهات',
    ];
    $openAlerts = $alerts->where('status', '!==', \App\Enums\AlertStatus::RESOLVED);
@endphp

<x-app-layout area="counselor" :title="$student->user->name">
    <a href="{{ route('counselor.students.index') }}" class="btn-ghost -ms-3 mb-2 px-3">
        <x-icon name="chevron-left" class="h-4 w-4 rotate-180" /> العودة إلى الطالبات
    </a>

    {{-- Header summary (brief §59) --}}
    <section class="card p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold text-ink">{{ $student->user->name }}</h1>
                <p class="mt-1 text-sm text-muted">{{ $student->classroom?->label() ?? 'بدون فصل' }} · <span dir="ltr">{{ $student->student_code }}</span></p>
            </div>
            <x-student-status-badge :status="$student->follow_up_status" />
        </div>
        <dl class="mt-5 grid grid-cols-2 gap-4 text-sm sm:grid-cols-4 lg:grid-cols-7">
            <div><dt class="text-muted">الإنجاز</dt><dd class="mt-1 font-heading text-lg font-bold">{{ $summary['completion']['percentage'] }}%</dd></div>
            <div><dt class="text-muted">آخر قدرات</dt><dd class="mt-1 font-heading text-lg font-bold">{{ $exams['qudurat']['latest'] ?? '—' }}</dd></div>
            <div><dt class="text-muted">أفضل قدرات</dt><dd class="mt-1 font-heading text-lg font-bold">{{ $exams['qudurat']['best'] ?? '—' }}</dd></div>
            <div><dt class="text-muted">الهدف</dt><dd class="mt-1 font-heading text-lg font-bold">{{ $exams['qudurat']['target'] }}</dd></div>
            <div><dt class="text-muted">آخر تحصيلي</dt><dd class="mt-1 font-heading text-lg font-bold">{{ $exams['tahsili']['latest'] ?? '—' }}</dd></div>
            <div>
                <dt class="text-muted">الاختبار القادم</dt>
                <dd class="mt-1 font-medium">{{ $next ? $next['attempt']->exam_type->label().' · '.\App\Support\ArabicDays::until($next['days']) : '—' }}</dd>
            </div>
            <div><dt class="text-muted">آخر نشاط</dt><dd class="mt-1 font-medium" dir="ltr">{{ $summary['last_activity_at']?->format('Y-m-d') ?? '—' }}</dd></div>
        </dl>
    </section>

    <div class="mt-6" x-data="{ tab: (location.hash || '#overview').slice(1) }" x-on:hashchange.window="tab = (location.hash || '#overview').slice(1)">
        <div class="-mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0">
            <div class="flex min-w-max gap-1 rounded-xl bg-brand-50 p-1" role="tablist" aria-label="أقسام ملف الطالبة">
                @foreach ($tabs as $key => $label)
                    <a href="#{{ $key }}" role="tab" id="tab-{{ $key }}" aria-controls="panel-{{ $key }}"
                        :aria-selected="(tab === '{{ $key }}').toString()"
                        :class="tab === '{{ $key }}' ? 'bg-surface text-brand-800 shadow-sm' : 'text-muted hover:text-ink'"
                        class="inline-flex min-h-[40px] items-center gap-1 rounded-lg px-3 text-sm font-bold transition">
                        {{ $label }}
                        @if ($key === 'alerts' && $openAlerts->isNotEmpty()) <x-badge color="danger">{{ $openAlerts->count() }}</x-badge> @endif
                        @if ($key === 'notes' && $notes->isNotEmpty()) <x-badge color="gray">{{ $notes->count() }}</x-badge> @endif
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Overview --}}
        <section id="panel-overview" role="tabpanel" aria-labelledby="tab-overview" x-show="tab === 'overview'" class="mt-6 space-y-6">
            <x-student-progress-card :summary="$summary" />
            @if ($canFollowUp)
                <form method="POST" action="{{ route('counselor.students.follow-up', $student) }}" class="card flex flex-col gap-3 p-5 sm:flex-row sm:items-end">
                    @csrf
                    @method('PATCH')
                    <div class="sm:w-72">
                        <x-form.select name="follow_up_status" label="حالة المتابعة" :value="$student->follow_up_status->value"
                            :options="collect($followUpStatuses)->mapWithKeys(fn ($s) => [$s->value => $s->label()])" />
                    </div>
                    <button class="btn-primary">حفظ الحالة</button>
                    @if ($student->follow_up_updated_at)
                        <p class="text-xs text-muted">آخر تحديث: <span dir="ltr">{{ $student->follow_up_updated_at->format('Y-m-d') }}</span></p>
                    @endif
                </form>
            @endif
            @if ($openAlerts->isNotEmpty())
                <div class="space-y-3">
                    @foreach ($openAlerts->take(3) as $alert)
                        <x-follow-up-alert :alert="$alert" :actions="$canFollowUp" />
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Scores --}}
        <section id="panel-scores" role="tabpanel" aria-labelledby="tab-scores" x-show="tab === 'scores'" x-cloak class="mt-6 grid gap-4 lg:grid-cols-2">
            @foreach ($exams as $exam)
                <x-score-summary :exam="$exam" />
            @endforeach
        </section>

        {{-- Exams --}}
        <section id="panel-exams" role="tabpanel" aria-labelledby="tab-exams" x-show="tab === 'exams'" x-cloak class="mt-6 space-y-6">
            @foreach ($exams as $exam)
                <div>
                    <h2 class="font-heading font-bold text-ink">{{ $exam['type']->label() }}</h2>
                    @if ($exam['attempts']->isEmpty())
                        <p class="mt-2 text-sm text-muted">لا توجد محاولات مسجّلة.</p>
                    @else
                        <x-table class="mt-2">
                            <x-slot:head>
                                <th scope="col" class="px-4 py-3 text-start">المحاولة</th>
                                <th scope="col" class="px-4 py-3 text-start">الحالة</th>
                                <th scope="col" class="px-4 py-3 text-start">التاريخ</th>
                                <th scope="col" class="px-4 py-3 text-start">الدرجة</th>
                                <th scope="col" class="px-4 py-3 text-start">الهدف</th>
                            </x-slot:head>
                            @foreach ($exam['attempts'] as $attempt)
                                <tr>
                                    <td class="px-4 py-3">{{ $attempt->attempt_number }}</td>
                                    <td class="px-4 py-3"><x-badge :color="$attempt->booking_status->color()">{{ $attempt->booking_status->label() }}</x-badge></td>
                                    <td class="whitespace-nowrap px-4 py-3" dir="ltr">{{ $attempt->exam_date?->format('Y-m-d') ?? '—' }}</td>
                                    <td class="px-4 py-3 font-bold">{{ $attempt->score ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $attempt->target_score ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </x-table>
                    @endif
                </div>
            @endforeach
        </section>

        {{-- Activity --}}
        <section id="panel-activity" role="tabpanel" aria-labelledby="tab-activity" x-show="tab === 'activity'" x-cloak class="mt-6">
            @if ($activities->isEmpty())
                <x-empty-state icon="clock" title="لا يوجد نشاط مسجّل" />
            @else
                <ul class="card divide-y divide-line">
                    @foreach ($activities as $activity)
                        <li class="flex flex-wrap items-center justify-between gap-2 p-4 text-sm">
                            <span>
                                <span class="font-medium text-ink">{{ $activity->event_type->label() }}</span>
                                @if ($activity->subject instanceof \App\Models\Content)
                                    <span class="text-muted">— {{ $activity->subject->title }}</span>
                                @elseif ($activity->subject instanceof \App\Models\ExamAttempt)
                                    <span class="text-muted">— {{ $activity->subject->exam_type->label() }} (المحاولة {{ $activity->subject->attempt_number }})</span>
                                @endif
                            </span>
                            <span class="text-xs text-muted" dir="ltr">{{ $activity->created_at->format('Y-m-d H:i') }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Content --}}
        <section id="panel-content" role="tabpanel" aria-labelledby="tab-content" x-show="tab === 'content'" x-cloak class="mt-6 grid gap-6 lg:grid-cols-2">
            @foreach (['قيد التقدم' => $inProgress, 'مكتمل' => $completed] as $heading => $items)
                <div>
                    <h2 class="font-heading font-bold text-ink">{{ $heading }}</h2>
                    @if ($items->isEmpty())
                        <p class="mt-2 text-sm text-muted">لا يوجد.</p>
                    @else
                        <ul class="card mt-2 divide-y divide-line">
                            @foreach ($items as $item)
                                <li class="flex items-center justify-between gap-2 p-3 text-sm">
                                    <span>{{ $item->content->title }}</span>
                                    <span class="text-xs text-muted" dir="ltr">{{ ($item->completed_at ?? $item->last_viewed_at)?->format('Y-m-d') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach

            <div class="lg:col-span-2">
                <h2 class="font-heading font-bold text-ink">الاختبارات القصيرة</h2>
                @if ($quizAttempts->isEmpty())
                    <p class="mt-2 text-sm text-muted">لم تحلّ الطالبة أي اختبار قصير بعد.</p>
                @else
                    <ul class="card mt-2 divide-y divide-line">
                        @foreach ($quizAttempts as $attempt)
                            <li class="flex items-center justify-between gap-3 p-3 text-sm">
                                <span class="line-clamp-1">{{ $attempt->quiz->content->title }}</span>
                                <span class="flex shrink-0 items-center gap-2">
                                    <x-badge :color="$attempt->passed ? 'success' : 'warning'"><bdi>{{ $attempt->percentage }}%</bdi></x-badge>
                                    <span class="text-xs text-muted" dir="ltr">{{ $attempt->submitted_at->format('Y-m-d') }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>

        {{-- Challenges (v0.7) --}}
        <section id="panel-challenges" role="tabpanel" aria-labelledby="tab-challenges" x-show="tab === 'challenges'" x-cloak class="mt-6">
            @if ($challengeAnswers->isEmpty())
                <x-empty-state icon="bolt" title="لم تُجب الطالبة على أي تحدٍّ بعد" />
            @else
                <p class="mb-3 text-sm text-muted">
                    إجابات صحيحة: {{ $challengeAnswers->where('is_correct', true)->count() }} من {{ $challengeAnswers->count() }} (آخر {{ $challengeAnswers->count() }} إجابة)
                </p>
                <ul class="card divide-y divide-line">
                    @foreach ($challengeAnswers as $answer)
                        <li class="flex items-center justify-between gap-3 p-4 text-sm">
                            <span class="line-clamp-1">{{ $answer->question->section->label() }} — {{ $answer->question->prompt }}</span>
                            <span class="flex shrink-0 items-center gap-2">
                                <x-badge :color="$answer->is_correct ? 'success' : 'danger'">{{ $answer->is_correct ? 'صحيحة' : 'غير صحيحة' }}</x-badge>
                                <span class="text-xs text-muted" dir="ltr">{{ $answer->answered_at->format('Y-m-d') }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Notes --}}
        <section id="panel-notes" role="tabpanel" aria-labelledby="tab-notes" x-show="tab === 'notes'" x-cloak class="mt-6 space-y-4">
            @if ($canFollowUp)
                <form method="POST" action="{{ route('counselor.students.notes.store', $student) }}#notes" class="card space-y-3 p-5">
                    @csrf
                    <x-input-label for="note" value="ملاحظة جديدة" />
                    <textarea id="note" name="note" rows="3" required class="block w-full rounded-xl border-line bg-white text-ink shadow-sm focus:border-brand-600 focus:ring-brand-600">{{ old('note') }}</textarea>
                    <x-input-error :messages="$errors->get('note')" />
                    <x-form.checkbox name="share_with_student" label="مشاركة الملاحظة مع الطالبة" hint="الملاحظات خاصة افتراضيًا ولا تراها الطالبة إلا عند تفعيل هذا الخيار." />
                    <x-primary-button>إضافة الملاحظة</x-primary-button>
                </form>
            @endif
            @forelse ($notes as $note)
                <article class="card p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2 text-sm">
                            <span class="font-medium text-ink">{{ $note->counselor?->name ?? '—' }}</span>
                            <x-badge :color="$note->is_private ? 'gray' : 'brand'">{{ $note->is_private ? 'خاصة' : 'تراها الطالبة' }}</x-badge>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-muted" dir="ltr">{{ $note->created_at->format('Y-m-d H:i') }}</span>
                            @can('delete', $note)
                                <x-delete-button :action="route('counselor.students.notes.destroy', [$student, $note])" confirm="حذف هذه الملاحظة؟" />
                            @endcan
                        </div>
                    </div>
                    <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-ink">{{ $note->note }}</p>
                </article>
            @empty
                <x-empty-state icon="document-text" title="لا توجد ملاحظات" />
            @endforelse
        </section>

        {{-- Alerts --}}
        <section id="panel-alerts" role="tabpanel" aria-labelledby="tab-alerts" x-show="tab === 'alerts'" x-cloak class="mt-6 space-y-3">
            @forelse ($alerts as $alert)
                <x-follow-up-alert :alert="$alert" :actions="$canFollowUp" />
            @empty
                <x-empty-state icon="bell" title="لا توجد تنبيهات" />
            @endforelse
        </section>
    </div>
</x-app-layout>
