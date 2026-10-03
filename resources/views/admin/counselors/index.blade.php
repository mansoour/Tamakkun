<x-app-layout area="admin" title="الموجهات">
    <x-page-header title="الموجهات الطلابيات" description="حسابات الموجهات وعدد الطالبات المسندات لكل موجهة.">
        <x-slot:actions>
            <a href="{{ route('admin.counselors.create') }}" class="btn-primary"><x-icon name="user-circle" /> إضافة موجهة</a>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-end">
        <div class="sm:w-80"><x-form.input name="q" label="بحث" :value="request('q')" placeholder="الاسم أو اسم المستخدم أو البريد" /></div>
        <button class="btn-secondary">بحث</button>
    </form>

    <div class="mt-6">
        @if ($counselors->isEmpty())
            <x-empty-state icon="user-circle" title="لا توجد موجهات" />
        @else
            <x-table>
                <x-slot:head>
                    <th scope="col" class="px-4 py-3 text-start">الموجهة</th>
                    <th scope="col" class="px-4 py-3 text-start">اسم المستخدم</th>
                    <th scope="col" class="px-4 py-3 text-start">المدرسة</th>
                    <th scope="col" class="px-4 py-3 text-start">الطالبات</th>
                    <th scope="col" class="px-4 py-3 text-start">الحالة</th>
                    <th scope="col" class="px-4 py-3"><span class="sr-only">إجراءات</span></th>
                </x-slot:head>
                @foreach ($counselors as $counselor)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $counselor->user->name }}</p>
                            <p class="text-xs text-muted" dir="ltr">{{ $counselor->user->email }}</p>
                        </td>
                        <td class="px-4 py-3" dir="ltr">{{ $counselor->user->username }}</td>
                        <td class="px-4 py-3">{{ $counselor->school->name }}</td>
                        <td class="px-4 py-3">{{ $counselor->user->assigned_students_count }}</td>
                        <td class="px-4 py-3"><x-badge :color="$counselor->user->status->color()">{{ $counselor->user->status->label() }}</x-badge></td>
                        <td class="px-4 py-3 text-end">
                            <div class="flex items-center justify-end gap-1">
                                @can('users.view-as')
                                    @if ($counselor->user->isActive())
                                        <form method="POST" action="{{ route('admin.users.view-as', $counselor->user) }}">
                                            @csrf
                                            <button class="btn-ghost min-h-[40px] px-3" title="عرض المنصة كما يراها هذا الحساب (قراءة فقط)">
                                                <x-icon name="eye" /> <span class="sr-only sm:not-sr-only">عرض كما تراه</span>
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                                <a href="{{ route('admin.counselors.edit', $counselor) }}" class="btn-ghost min-h-[40px] px-3">تعديل</a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>
            {{ $counselors->links() }}
        @endif
    </div>
</x-app-layout>
