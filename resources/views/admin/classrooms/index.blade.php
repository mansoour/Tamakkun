<x-app-layout area="admin" title="الفصول">
    <x-page-header title="الفصول" description="الفصول داخل كل صف، وتُسجَّل فيها الطالبات.">
        <x-slot:actions>
            <a href="{{ route('admin.classes.create', request()->only('grade_id')) }}" class="btn-primary"><x-icon name="squares-2x2" /> إضافة فصل</a>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-end">
        <div class="sm:w-96">
            <x-form.select name="grade_id" label="الصف" :options="$grades" :value="request('grade_id')" placeholder="كل الصفوف" />
        </div>
        <button class="btn-secondary">تصفية</button>
    </form>

    <div class="mt-6">
        @if ($classrooms->isEmpty())
            <x-empty-state icon="squares-2x2" title="لا توجد فصول" />
        @else
            <x-table>
                <x-slot:head>
                    <th scope="col" class="px-4 py-3 text-start">الفصل</th>
                    <th scope="col" class="px-4 py-3 text-start">الصف والعام</th>
                    <th scope="col" class="px-4 py-3 text-start">الطالبات</th>
                    <th scope="col" class="px-4 py-3"><span class="sr-only">إجراءات</span></th>
                </x-slot:head>
                @foreach ($classrooms as $classroom)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $classroom->name }}</td>
                        <td class="px-4 py-3">{{ $classroom->grade->fullName() }}</td>
                        <td class="px-4 py-3">{{ $classroom->student_profiles_count }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-1">
                                <a href="{{ route('admin.classes.edit', $classroom) }}" class="btn-ghost min-h-[40px] px-3">تعديل</a>
                                <x-delete-button :action="route('admin.classes.destroy', $classroom)" />
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>
            {{ $classrooms->links() }}
        @endif
    </div>
</x-app-layout>
