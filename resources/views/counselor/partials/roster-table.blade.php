{{-- $rows: StudentRosterService rows (collection or paginator) --}}
<x-table>
    <x-slot:head>
        <th scope="col" class="px-3 py-3 text-start">الطالبة</th>
        <th scope="col" class="px-3 py-3 text-start">الفصل</th>
        <th scope="col" class="px-3 py-3 text-start">الإنجاز</th>
        <th scope="col" class="px-3 py-3 text-start">آخر نشاط</th>
        <th scope="col" class="px-3 py-3 text-start">آخر قدرات</th>
        <th scope="col" class="px-3 py-3 text-start">أفضل قدرات</th>
        <th scope="col" class="px-3 py-3 text-start">الهدف</th>
        <th scope="col" class="px-3 py-3 text-start">التحصيلي</th>
        <th scope="col" class="px-3 py-3 text-start">الاختبار القادم</th>
        <th scope="col" class="px-3 py-3 text-start">الحجز</th>
        <th scope="col" class="px-3 py-3 text-start">المتابعة</th>
    </x-slot:head>
    @foreach ($rows as $row)
        <tr>
            <td class="px-3 py-3">
                <a href="{{ route('counselor.students.show', $row['profile']) }}" class="font-medium text-brand-700 underline-offset-4 hover:underline">{{ $row['name'] }}</a>
                @if ($row['open_alerts'] > 0)
                    <x-badge color="danger" class="ms-1">{{ $row['open_alerts'] }} تنبيه</x-badge>
                @endif
            </td>
            <td class="whitespace-nowrap px-3 py-3">{{ $row['classroom'] ?? '—' }}</td>
            <td class="px-3 py-3">{{ $row['completion'] }}%</td>
            <td class="whitespace-nowrap px-3 py-3">
                <span dir="ltr">{{ $row['last_activity']?->format('Y-m-d') ?? '—' }}</span>
                @if ($row['inactive']) <x-badge color="warning">غير نشطة</x-badge> @endif
            </td>
            <td class="px-3 py-3 font-bold">{{ $row['qudurat']['latest'] ?? '—' }}</td>
            <td class="px-3 py-3">{{ $row['qudurat']['best'] ?? '—' }}</td>
            <td class="px-3 py-3">{{ $row['qudurat']['target'] }}</td>
            <td class="px-3 py-3">{{ $row['tahsili']['latest'] ?? '—' }}</td>
            <td class="whitespace-nowrap px-3 py-3">
                @if ($row['next_exam'])
                    {{ $row['next_exam']['attempt']->exam_type->label() }} · {{ \App\Support\ArabicDays::until($row['next_exam']['days']) }}
                @else
                    —
                @endif
            </td>
            <td class="px-3 py-3">
                <x-badge :color="$row['booked_any'] ? 'success' : 'warning'">{{ $row['booked_any'] ? 'محجوز' : 'لم تحجز' }}</x-badge>
            </td>
            <td class="px-3 py-3"><x-student-status-badge :status="$row['follow_up']" /></td>
        </tr>
    @endforeach
</x-table>
