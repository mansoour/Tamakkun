@php($editing = $counselor->exists)

<x-app-layout area="admin" :title="$editing ? 'تعديل موجهة' : 'إضافة موجهة'">
    <x-page-header :title="$editing ? 'تعديل بيانات الموجهة' : 'إضافة موجهة'" />

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ $editing ? route('admin.counselors.update', $counselor) : route('admin.counselors.store') }}" class="card space-y-5 p-6 lg:col-span-2">
            @csrf
            @if ($editing) @method('PUT') @endif

            <x-form.input name="name" label="الاسم الكامل" :value="$counselor->user?->name" required />
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.input name="username" label="اسم المستخدم" :value="$counselor->user?->username" required />
                <x-form.input name="email" type="email" label="البريد الإلكتروني" :value="$counselor->user?->email" />
            </div>
            <x-form.input name="password" type="password" :label="$editing ? 'كلمة مرور جديدة (اختياري)' : 'كلمة المرور الأولية'"
                autocomplete="new-password" :required="! $editing" hint="8 أحرف على الأقل." />
            <x-form.select name="school_id" label="المدرسة" :options="$schools" :value="$counselor->school_id" placeholder="اختاري المدرسة" required />
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.input name="job_title" label="المسمى الوظيفي" :value="$counselor->job_title" />
                <x-form.input name="phone" label="رقم الجوال" :value="$counselor->phone" />
            </div>

            @unless ($editing)
                <x-form.select name="status" label="حالة الحساب" :value="old('status', 'active')"
                    :options="collect($statuses)->mapWithKeys(fn ($s) => [$s->value => $s->label()])" />
            @endunless

            <div class="flex gap-2">
                <x-primary-button>حفظ</x-primary-button>
                <a href="{{ route('admin.counselors.index') }}" class="btn-secondary">إلغاء</a>
            </div>
        </form>

        @if ($editing)
            @include('admin.partials.account-status', ['user' => $counselor->user])
        @endif
    </div>
</x-app-layout>
