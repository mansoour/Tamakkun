<x-app-layout area="counselor" title="الطالبات">
    <x-page-header title="طالباتي" description="الطالبات المسندات إليكِ." />

    <form method="GET" class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-end">
        <div class="sm:w-80"><x-form.input name="q" label="بحث" :value="request('q')" placeholder="الاسم أو رقم الطالبة" /></div>
        <button class="btn-secondary">بحث</button>
    </form>

    <div class="mt-6">
        @if ($students->isEmpty())
            <x-empty-state icon="users" title="لا توجد طالبات" description="لم تُسند إليكِ طالبات بعد، أو لا توجد نتائج مطابقة للبحث." />
        @else
            <x-table>
                <x-slot:head>
                    <th scope="col" class="px-4 py-3 text-start">الطالبة</th>
                    <th scope="col" class="px-4 py-3 text-start">رقم الطالبة</th>
                    <th scope="col" class="px-4 py-3 text-start">الفصل</th>
                    <th scope="col" class="px-4 py-3 text-start">الحالة</th>
                </x-slot:head>
                @foreach ($students as $student)
                    <tr>
                        <td class="px-4 py-3">
                            <a href="{{ route('counselor.students.show', $student) }}" class="font-medium text-brand-700 underline-offset-4 hover:underline">{{ $student->user->name }}</a>
                        </td>
                        <td class="px-4 py-3" dir="ltr">{{ $student->student_code }}</td>
                        <td class="px-4 py-3">{{ $student->classroom?->label() ?? '—' }}</td>
                        <td class="px-4 py-3"><x-badge :color="$student->user->status->color()">{{ $student->user->status->label() }}</x-badge></td>
                    </tr>
                @endforeach
            </x-table>
            {{ $students->links() }}
        @endif
    </div>
</x-app-layout>
