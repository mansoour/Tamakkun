<x-app-layout area="admin" :title="$school->exists ? 'تعديل مدرسة' : 'إضافة مدرسة'">
    <x-page-header :title="$school->exists ? 'تعديل مدرسة' : 'إضافة مدرسة'" />

    <form method="POST" action="{{ $school->exists ? route('admin.schools.update', $school) : route('admin.schools.store') }}" class="card mt-6 max-w-2xl space-y-5 p-6">
        @csrf
        @if ($school->exists) @method('PUT') @endif

        <x-form.input name="name" label="اسم المدرسة" :value="$school->name" required />
        <x-form.input name="city" label="المدينة" :value="$school->city" />
        <x-form.checkbox name="is_active" label="المدرسة نشطة" :checked="$school->is_active" />

        <div class="flex gap-2">
            <x-primary-button>حفظ</x-primary-button>
            <a href="{{ route('admin.schools.index') }}" class="btn-secondary">إلغاء</a>
        </div>
    </form>
</x-app-layout>
