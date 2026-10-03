<x-app-layout area="admin" title="دفعة اليوم">
    <x-page-header title="دفعة اليوم" description="العنصر المؤرَّخ بتاريخ اليوم يظهر أولًا، وإلا تتناوب العناصر غير المؤرخة يوميًا.">
        <x-slot:actions>
            <a href="{{ route('admin.motivations.create') }}" class="btn-primary"><x-icon name="sparkles" /> إضافة</a>
        </x-slot:actions>
    </x-page-header>

    @if ($today)
        <x-alert class="mt-6" title="المعروض اليوم">{{ $today->title }}</x-alert>
    @endif

    <div class="mt-6">
        @if ($motivations->isEmpty())
            <x-empty-state icon="sparkles" title="لا توجد عناصر بعد" />
        @else
            <x-table>
                <x-slot:head>
                    <th scope="col" class="px-4 py-3 text-start">العنوان</th>
                    <th scope="col" class="px-4 py-3 text-start">النوع</th>
                    <th scope="col" class="px-4 py-3 text-start">تاريخ العرض</th>
                    <th scope="col" class="px-4 py-3 text-start">الحالة</th>
                    <th scope="col" class="px-4 py-3"><span class="sr-only">إجراءات</span></th>
                </x-slot:head>
                @foreach ($motivations as $motivation)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $motivation->title }}</td>
                        <td class="px-4 py-3">{{ $motivation->media_type->label() }}</td>
                        <td class="whitespace-nowrap px-4 py-3" dir="ltr">{{ $motivation->publish_date?->format('Y-m-d') ?? 'تناوب' }}</td>
                        <td class="px-4 py-3"><x-badge :color="$motivation->is_active ? 'success' : 'gray'">{{ $motivation->is_active ? 'نشط' : 'موقوف' }}</x-badge></td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-1">
                                <a href="{{ route('admin.motivations.edit', $motivation) }}" class="btn-ghost min-h-[40px] px-3">تعديل</a>
                                <x-delete-button :action="route('admin.motivations.destroy', $motivation)" />
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>
            {{ $motivations->links() }}
        @endif
    </div>
</x-app-layout>
