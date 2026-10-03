@php($editing = $student->exists)

<x-app-layout area="admin" :title="$editing ? 'تعديل طالبة' : 'إضافة طالبة'">
    <x-page-header :title="$editing ? 'تعديل بيانات الطالبة' : 'إضافة طالبة'" />

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ $editing ? route('admin.students.update', $student) : route('admin.students.store') }}"
            class="card space-y-5 p-6 lg:col-span-2"
            x-data="{
                schoolId: @js((string) old('school_id', $student->school_id)),
                classroomId: @js((string) old('classroom_id', $student->classroom_id)),
                counselorId: @js((string) old('counselor_id', $student->counselor_id)),
                classrooms: @js($classrooms),
                counselors: @js($counselors),
            }">
            @csrf
            @if ($editing) @method('PUT') @endif

            <x-form.input name="name" label="الاسم الكامل" :value="$student->user?->name" required />
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.input name="student_code" label="رقم الطالبة" :value="$student->student_code" dir="ltr" class="text-start" required
                    hint="أحرف لاتينية وأرقام فقط. لا تستخدمي رقم الهوية." />
                <x-form.input name="username" label="اسم المستخدم" :value="$student->user?->username" dir="ltr" class="text-start"
                    hint="يُستخدم لتسجيل الدخول. يُترك فارغًا ليكون مثل رقم الطالبة." />
            </div>
            <x-form.input name="password" type="password" :label="$editing ? 'كلمة مرور جديدة (اختياري)' : 'كلمة المرور الأولية'"
                autocomplete="new-password" dir="ltr" class="text-start" :required="! $editing" hint="8 أحرف على الأقل." />

            <x-form.select name="school_id" label="المدرسة" :options="$schools" :value="$student->school_id" placeholder="اختاري المدرسة"
                x-model="schoolId" x-on:change="classroomId = ''; counselorId = ''" required />

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <x-input-label for="classroom_id" value="الفصل" />
                    <select id="classroom_id" name="classroom_id" x-model="classroomId"
                        class="block w-full min-h-[44px] rounded-xl border-line bg-white text-ink shadow-sm focus:border-brand-600 focus:ring-brand-600">
                        <option value="">بدون فصل</option>
                        <template x-for="c in (classrooms[schoolId] || [])" :key="c.id">
                            <option :value="String(c.id)" x-text="c.label" :selected="String(c.id) === classroomId"></option>
                        </template>
                    </select>
                    <x-input-error :messages="$errors->get('classroom_id')" />
                </div>
                <div>
                    <x-input-label for="counselor_id" value="الموجهة الطلابية" />
                    <select id="counselor_id" name="counselor_id" x-model="counselorId"
                        class="block w-full min-h-[44px] rounded-xl border-line bg-white text-ink shadow-sm focus:border-brand-600 focus:ring-brand-600">
                        <option value="">بدون موجهة</option>
                        <template x-for="c in (counselors[schoolId] || [])" :key="c.id">
                            <option :value="String(c.id)" x-text="c.label" :selected="String(c.id) === counselorId"></option>
                        </template>
                    </select>
                    <x-input-error :messages="$errors->get('counselor_id')" />
                </div>
            </div>

            @unless ($editing)
                <x-form.select name="status" label="حالة الحساب" :value="old('status', 'active')"
                    :options="collect($statuses)->mapWithKeys(fn ($s) => [$s->value => $s->label()])" />
            @endunless

            <div class="flex gap-2">
                <x-primary-button>حفظ</x-primary-button>
                <a href="{{ route('admin.students.index') }}" class="btn-secondary">إلغاء</a>
            </div>
        </form>

        @if ($editing)
            @include('admin.partials.account-status', ['user' => $student->user])
        @endif
    </div>
</x-app-layout>
