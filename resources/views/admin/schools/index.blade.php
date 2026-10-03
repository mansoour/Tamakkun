<x-app-layout area="admin" title="المدارس">
    <x-page-header title="المدارس" description="المدارس المسجلة في المنصة.">
        <x-slot:actions>
            <a href="{{ route('admin.schools.create') }}" class="btn-primary"><x-icon name="building-library" /> إضافة مدرسة</a>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6">
        @if ($schools->isEmpty())
            <x-empty-state icon="building-library" title="لا توجد مدارس بعد" description="ابدئي بإضافة المدرسة، ثم العام الدراسي والصفوف والفصول." />
        @else
            <x-table>
                <x-slot:head>
                    <th scope="col" class="px-4 py-3 text-start">المدرسة</th>
                    <th scope="col" class="px-4 py-3 text-start">المدينة</th>
                    <th scope="col" class="px-4 py-3 text-start">الأعوام</th>
                    <th scope="col" class="px-4 py-3 text-start">الطالبات</th>
                    <th scope="col" class="px-4 py-3 text-start">الموجهات</th>
                    <th scope="col" class="px-4 py-3 text-start">الحالة</th>
                    <th scope="col" class="px-4 py-3"><span class="sr-only">إجراءات</span></th>
                </x-slot:head>
                @foreach ($schools as $school)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $school->name }}</td>
                        <td class="px-4 py-3 text-muted">{{ $school->city ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <a class="text-brand-700 underline-offset-4 hover:underline" href="{{ route('admin.academic-years.index', ['school_id' => $school->id]) }}">{{ $school->academic_years_count }}</a>
                        </td>
                        <td class="px-4 py-3">{{ $school->student_profiles_count }}</td>
                        <td class="px-4 py-3">{{ $school->counselor_profiles_count }}</td>
                        <td class="px-4 py-3">
                            <x-badge :color="$school->is_active ? 'success' : 'gray'">{{ $school->is_active ? 'نشطة' : 'غير نشطة' }}</x-badge>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-1">
                                <a href="{{ route('admin.schools.edit', $school) }}" class="btn-ghost min-h-[40px] px-3">تعديل</a>
                                <x-delete-button :action="route('admin.schools.destroy', $school)" />
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>
            {{ $schools->links() }}
        @endif
    </div>
</x-app-layout>
