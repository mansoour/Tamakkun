<x-app-layout area="counselor" title="النتائج">
    <x-page-header title="النتائج" description="مرتبة حسب التحسن في القدرات." />

    <div class="mt-6">
        @if ($rows->isEmpty())
            <x-empty-state icon="presentation-chart-line" title="لم تُسجّل نتائج بعد" />
        @else
            <x-table>
                <x-slot:head>
                    <th scope="col" class="px-4 py-3 text-start">الطالبة</th>
                    <th scope="col" class="px-4 py-3 text-start">آخر قدرات</th>
                    <th scope="col" class="px-4 py-3 text-start">أفضل قدرات</th>
                    <th scope="col" class="px-4 py-3 text-start">التحسن</th>
                    <th scope="col" class="px-4 py-3 text-start">الهدف</th>
                    <th scope="col" class="px-4 py-3 text-start">آخر تحصيلي</th>
                    <th scope="col" class="px-4 py-3 text-start">أفضل تحصيلي</th>
                </x-slot:head>
                @foreach ($rows as $row)
                    @php($improvement = $row['qudurat']['improvement'])
                    <tr>
                        <td class="px-4 py-3"><a href="{{ route('counselor.students.show', $row['profile']) }}" class="font-medium text-brand-700 underline-offset-4 hover:underline">{{ $row['name'] }}</a></td>
                        <td class="px-4 py-3 font-bold">{{ $row['qudurat']['latest'] ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $row['qudurat']['best'] ?? '—' }}</td>
                        <td @class(['px-4 py-3 font-medium', 'text-emerald-700' => $improvement > 0, 'text-red-700' => $improvement < 0])>
                            <bdi>{{ $improvement === null ? '—' : ($improvement > 0 ? '+' : '').$improvement }}</bdi>
                        </td>
                        <td class="px-4 py-3">{{ $row['qudurat']['target'] }}</td>
                        <td class="px-4 py-3 font-bold">{{ $row['tahsili']['latest'] ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $row['tahsili']['best'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </x-table>
        @endif
    </div>
</x-app-layout>
