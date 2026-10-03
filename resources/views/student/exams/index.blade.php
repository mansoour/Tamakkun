<x-app-layout area="student" title="موعدي ودرجتي">
    <x-page-header title="موعدي ودرجتي" description="سجّلي حجز اختباراتك ومواعيدها ودرجاتك، وتابعي تقدّمك نحو هدفك.">
        <x-slot:actions>
            <a href="{{ route('student.exams.create') }}" class="btn-primary"><x-icon name="calendar-days" /> إضافة اختبار</a>
        </x-slot:actions>
    </x-page-header>

    <x-exam-countdown :next="$next" editable class="mt-6" />

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        @foreach ($summary as $exam)
            <x-score-summary :exam="$exam" />
        @endforeach
    </div>

    @foreach ($summary as $exam)
        <section class="mt-8" aria-labelledby="attempts-{{ $exam['type']->value }}">
            <div class="flex items-center justify-between gap-3">
                <h2 id="attempts-{{ $exam['type']->value }}" class="text-lg font-bold text-ink">محاولات {{ $exam['type']->label() }}</h2>
                <a href="{{ route('student.exams.create', ['type' => $exam['type']->value]) }}" class="btn-ghost min-h-[40px] px-3">إضافة محاولة</a>
            </div>

            @if ($exam['attempts']->isEmpty())
                <x-empty-state class="mt-3" icon="calendar-days" title="لم تُضيفي أي محاولة بعد" />
            @else
                <x-table class="mt-3">
                    <x-slot:head>
                        <th scope="col" class="px-4 py-3 text-start">المحاولة</th>
                        <th scope="col" class="px-4 py-3 text-start">الحالة</th>
                        <th scope="col" class="px-4 py-3 text-start">التاريخ</th>
                        <th scope="col" class="px-4 py-3 text-start">الدرجة</th>
                        <th scope="col" class="px-4 py-3 text-start">الهدف</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">إجراءات</span></th>
                    </x-slot:head>
                    @foreach ($exam['attempts'] as $attempt)
                        <tr>
                            <td class="px-4 py-3 font-medium">{{ $attempt->attempt_number }}</td>
                            <td class="px-4 py-3"><x-badge :color="$attempt->booking_status->color()">{{ $attempt->booking_status->label() }}</x-badge></td>
                            <td class="whitespace-nowrap px-4 py-3" dir="ltr">{{ $attempt->exam_date?->format('Y-m-d') ?? '—' }}</td>
                            <td class="px-4 py-3 font-bold">{{ $attempt->score ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $attempt->target_score ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('student.exams.edit', $attempt) }}" class="btn-ghost min-h-[40px] px-3">تعديل</a>
                                    <x-delete-button :action="route('student.exams.destroy', $attempt)" confirm="هل تريدين حذف هذه المحاولة؟" />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </x-table>
            @endif
        </section>
    @endforeach

    @if ($hasOfficialLinks)
        <p class="mt-8 text-sm text-muted">
            للحجز والاستعلام عن النتائج استخدمي <a href="{{ route('student.links') }}" class="font-medium text-brand-700 underline-offset-4 hover:underline">الروابط الرسمية</a>.
        </p>
    @endif
</x-app-layout>
