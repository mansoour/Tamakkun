<x-app-layout area="admin" title="الأعوام الدراسية">
    <x-page-header title="الأعوام الدراسية" description="لكل مدرسة عام دراسي حالي واحد تُربط به الصفوف والفصول.">
        <x-slot:actions>
            <a href="{{ route('admin.academic-years.create', request()->only('school_id')) }}" class="btn-primary"><x-icon name="calendar-days" /> إضافة عام دراسي</a>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-end">
        <div class="sm:w-72">
            <x-form.select name="school_id" label="المدرسة" :options="$schools" :value="request('school_id')" placeholder="كل المدارس" />
        </div>
        <button class="btn-secondary">تصفية</button>
    </form>

    <div class="mt-6">
        @if ($years->isEmpty())
            <x-empty-state icon="calendar-days" title="لا توجد أعوام دراسية" />
        @else
            <x-table>
                <x-slot:head>
                    <th scope="col" class="px-4 py-3 text-start">العام</th>
                    <th scope="col" class="px-4 py-3 text-start">المدرسة</th>
                    <th scope="col" class="px-4 py-3 text-start">الفترة</th>
                    <th scope="col" class="px-4 py-3 text-start">الصفوف</th>
                    <th scope="col" class="px-4 py-3"><span class="sr-only">إجراءات</span></th>
                </x-slot:head>
                @foreach ($years as $year)
                    <tr>
                        <td class="px-4 py-3 font-medium">
                            {{ $year->name }}
                            @if ($year->is_current) <x-badge color="success" class="ms-2">الحالي</x-badge> @endif
                        </td>
                        <td class="px-4 py-3">{{ $year->school->name }}</td>
                        <td class="px-4 py-3 text-muted" dir="ltr">{{ $year->starts_on?->format('Y-m-d') ?? '—' }} → {{ $year->ends_on?->format('Y-m-d') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <a class="text-brand-700 underline-offset-4 hover:underline" href="{{ route('admin.grades.index', ['academic_year_id' => $year->id]) }}">{{ $year->grades_count }}</a>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-1">
                                <a href="{{ route('admin.academic-years.edit', $year) }}" class="btn-ghost min-h-[40px] px-3">تعديل</a>
                                <x-delete-button :action="route('admin.academic-years.destroy', $year)" />
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>
            {{ $years->links() }}
        @endif
    </div>
</x-app-layout>
