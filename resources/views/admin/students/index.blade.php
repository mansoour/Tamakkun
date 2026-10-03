<x-app-layout area="admin" title="الطالبات">
    <x-page-header title="الطالبات" description="حسابات الطالبات وفصولهن والموجهة المسندة لكل طالبة.">
        <x-slot:actions>
            @can('students.import')
                <a href="{{ route('admin.imports.create') }}" class="btn-secondary"><x-icon name="document-text" /> استيراد من ملف</a>
            @endcan
            <a href="{{ route('admin.students.create') }}" class="btn-primary"><x-icon name="users" /> إضافة طالبة</a>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="card mt-6 grid gap-4 p-4 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
        <x-form.input name="q" label="بحث" :value="request('q')" placeholder="الاسم أو رقم الطالبة" />
        <x-form.select name="school_id" label="المدرسة" :options="$schools" :value="request('school_id')" placeholder="كل المدارس" />
        <x-form.select name="status" label="حالة الحساب" :options="collect($statuses)->mapWithKeys(fn ($s) => [$s->value => $s->label()])" :value="request('status')" placeholder="كل الحالات" />
        <div class="flex flex-wrap items-center gap-3">
            <x-form.checkbox name="unassigned" label="بدون موجهة" :checked="request()->boolean('unassigned')" />
            <button class="btn-secondary">تصفية</button>
        </div>
    </form>

    <div class="mt-6">
        @if ($students->isEmpty())
            <x-empty-state icon="users" title="لا توجد طالبات مطابقة" description="أضيفي طالبة يدويًا أو استوردي ملف CSV." />
        @else
            <x-table>
                <x-slot:head>
                    <th scope="col" class="px-4 py-3 text-start">الطالبة</th>
                    <th scope="col" class="px-4 py-3 text-start">رقم الطالبة</th>
                    <th scope="col" class="px-4 py-3 text-start">المدرسة</th>
                    <th scope="col" class="px-4 py-3 text-start">الفصل</th>
                    <th scope="col" class="px-4 py-3 text-start">الموجهة</th>
                    <th scope="col" class="px-4 py-3 text-start">الحالة</th>
                    <th scope="col" class="px-4 py-3"><span class="sr-only">إجراءات</span></th>
                </x-slot:head>
                @foreach ($students as $student)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $student->user->name }}</td>
                        <td class="px-4 py-3" dir="ltr">{{ $student->student_code }}</td>
                        <td class="px-4 py-3">{{ $student->school->name }}</td>
                        <td class="px-4 py-3">{{ $student->classroom?->label() ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $student->counselor?->name ?? '—' }}</td>
                        <td class="px-4 py-3"><x-badge :color="$student->user->status->color()">{{ $student->user->status->label() }}</x-badge></td>
                        <td class="px-4 py-3 text-end">
                            <div class="flex items-center justify-end gap-1">
                                @can('users.view-as')
                                    @if ($student->user->isActive())
                                        <form method="POST" action="{{ route('admin.users.view-as', $student->user) }}">
                                            @csrf
                                            <button class="btn-ghost min-h-[40px] px-3" title="عرض المنصة كما يراها هذا الحساب (قراءة فقط)">
                                                <x-icon name="eye" /> <span class="sr-only sm:not-sr-only">عرض كما تراه</span>
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                                <a href="{{ route('admin.students.edit', $student) }}" class="btn-ghost min-h-[40px] px-3">تعديل</a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>
            {{ $students->links() }}
        @endif
    </div>
</x-app-layout>
