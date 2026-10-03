<x-app-layout area="admin" title="الصفوف">
    <x-page-header title="الصفوف" description="الصفوف الدراسية داخل كل عام دراسي (مثل: الثالث الثانوي).">
        <x-slot:actions>
            <a href="{{ route('admin.grades.create', request()->only('academic_year_id')) }}" class="btn-primary"><x-icon name="academic-cap" /> إضافة صف</a>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-end">
        <div class="sm:w-96">
            <x-form.select name="academic_year_id" label="العام الدراسي" :options="$years" :value="request('academic_year_id')" placeholder="كل الأعوام" />
        </div>
        <button class="btn-secondary">تصفية</button>
    </form>

    <div class="mt-6">
        @if ($grades->isEmpty())
            <x-empty-state icon="academic-cap" title="لا توجد صفوف" />
        @else
            <x-table>
                <x-slot:head>
                    <th scope="col" class="px-4 py-3 text-start">الصف</th>
                    <th scope="col" class="px-4 py-3 text-start">العام الدراسي</th>
                    <th scope="col" class="px-4 py-3 text-start">المستوى</th>
                    <th scope="col" class="px-4 py-3 text-start">الفصول</th>
                    <th scope="col" class="px-4 py-3"><span class="sr-only">إجراءات</span></th>
                </x-slot:head>
                @foreach ($grades as $grade)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $grade->name }}</td>
                        <td class="px-4 py-3">{{ $grade->academicYear->fullName() }}</td>
                        <td class="px-4 py-3">{{ $grade->level ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <a class="text-brand-700 underline-offset-4 hover:underline" href="{{ route('admin.classes.index', ['grade_id' => $grade->id]) }}">{{ $grade->classrooms_count }}</a>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-1">
                                <a href="{{ route('admin.grades.edit', $grade) }}" class="btn-ghost min-h-[40px] px-3">تعديل</a>
                                <x-delete-button :action="route('admin.grades.destroy', $grade)" />
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>
            {{ $grades->links() }}
        @endif
    </div>
</x-app-layout>
