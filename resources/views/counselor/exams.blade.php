<x-app-layout area="counselor" title="الاختبارات القادمة">
    <x-page-header title="الاختبارات القادمة" />

    <section class="mt-6" aria-labelledby="upcoming-title">
        <h2 id="upcoming-title" class="text-lg font-semibold text-ink">اختبارات محجوزة</h2>
        @if ($upcoming->isEmpty())
            <x-empty-state class="mt-3" icon="calendar-days" title="لا توجد اختبارات محجوزة قادمة" />
        @else
            <x-table class="mt-3">
                <x-slot:head>
                    <th scope="col" class="px-4 py-3 text-start">الطالبة</th>
                    <th scope="col" class="px-4 py-3 text-start">الاختبار</th>
                    <th scope="col" class="px-4 py-3 text-start">التاريخ</th>
                    <th scope="col" class="px-4 py-3 text-start">المتبقي</th>
                    <th scope="col" class="px-4 py-3 text-start">الإنجاز</th>
                </x-slot:head>
                @foreach ($upcoming as $row)
                    <tr>
                        <td class="px-4 py-3"><a href="{{ route('counselor.students.show', $row['profile']) }}" class="font-medium text-brand-700 underline-offset-4 hover:underline">{{ $row['name'] }}</a></td>
                        <td class="px-4 py-3">{{ $row['next_exam']['attempt']->exam_type->label() }}</td>
                        <td class="whitespace-nowrap px-4 py-3" dir="ltr">{{ $row['next_exam']['attempt']->exam_date->format('Y-m-d') }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ \App\Support\ArabicDays::until($row['next_exam']['days']) }}</td>
                        <td class="px-4 py-3">{{ $row['completion'] }}%</td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </section>

    <section class="mt-8" aria-labelledby="not-booked-title">
        <h2 id="not-booked-title" class="text-lg font-semibold text-ink">لم يحجزن ({{ $notBooked->count() }})</h2>
        @if ($notBooked->isEmpty())
            <x-empty-state class="mt-3" icon="check-circle" title="كل الطالبات حجزن اختبارًا واحدًا على الأقل" />
        @else
            <ul class="card mt-3 divide-y divide-line">
                @foreach ($notBooked as $row)
                    <li class="flex items-center justify-between gap-3 p-4">
                        <a href="{{ route('counselor.students.show', $row['profile']) }}" class="font-medium text-brand-700 underline-offset-4 hover:underline">{{ $row['name'] }}</a>
                        <span class="text-sm text-muted">{{ $row['classroom'] ?? '—' }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-app-layout>
