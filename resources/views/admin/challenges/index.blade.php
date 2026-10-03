<x-app-layout area="admin" title="تحدي اليوم">
    <x-page-header title="تحدي اليوم" description="سؤال كمي وسؤال لفظي يوميًا افتراضيًا. يُرسل إشعار للطالبات صباح يوم التحدي.">
        <x-slot:actions>
            <a href="{{ route('admin.challenges.create') }}" class="btn-primary"><x-icon name="bolt" /> إضافة تحدٍّ</a>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6">
        @if ($challenges->isEmpty())
            <x-empty-state icon="bolt" title="لا توجد تحديات بعد" />
        @else
            <x-table>
                <x-slot:head>
                    <th scope="col" class="px-4 py-3 text-start">التاريخ</th>
                    <th scope="col" class="px-4 py-3 text-start">العنوان</th>
                    <th scope="col" class="px-4 py-3 text-start">الأسئلة</th>
                    <th scope="col" class="px-4 py-3 text-start">الإجابات</th>
                    <th scope="col" class="px-4 py-3 text-start">الحالة</th>
                    <th scope="col" class="px-4 py-3"><span class="sr-only">إجراءات</span></th>
                </x-slot:head>
                @foreach ($challenges as $challenge)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-3 font-medium" dir="ltr">{{ $challenge->challenge_date->format('Y-m-d') }}</td>
                        <td class="px-4 py-3">{{ $challenge->title ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $challenge->questions_count }}</td>
                        <td class="px-4 py-3">{{ $challenge->answers_count }}</td>
                        <td class="px-4 py-3"><x-badge :color="$challenge->is_published ? 'success' : 'warning'">{{ $challenge->is_published ? 'منشور' : 'مسودة' }}</x-badge></td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-1">
                                @if ($challenge->answers_count == 0)
                                    <a href="{{ route('admin.challenges.edit', $challenge) }}" class="btn-ghost min-h-[40px] px-3">تعديل</a>
                                @endif
                                <form method="POST" action="{{ route($challenge->is_published ? 'admin.challenges.unpublish' : 'admin.challenges.publish', $challenge) }}">
                                    @csrf
                                    <button class="btn-ghost min-h-[40px] px-3">{{ $challenge->is_published ? 'إلغاء النشر' : 'نشر' }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>
            {{ $challenges->links() }}
        @endif
    </div>
</x-app-layout>
