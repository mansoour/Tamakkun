<x-app-layout area="admin" :title="$grade->exists ? 'تعديل صف' : 'إضافة صف'">
    <x-page-header :title="$grade->exists ? 'تعديل صف' : 'إضافة صف'" />

    <form method="POST" action="{{ $grade->exists ? route('admin.grades.update', $grade) : route('admin.grades.store') }}" class="card mt-6 max-w-2xl space-y-5 p-6">
        @csrf
        @if ($grade->exists) @method('PUT') @endif

        <x-form.select name="academic_year_id" label="العام الدراسي" :options="$years" :value="$grade->academic_year_id" placeholder="اختاري العام الدراسي" required />
        <x-form.input name="name" label="اسم الصف" :value="$grade->name" hint="مثال: الثالث الثانوي" required />
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.input name="level" type="number" label="المستوى (1–12)" :value="$grade->level" min="1" max="12" hint="12 = الصف الثالث الثانوي" />
            <x-form.input name="sort_order" type="number" label="الترتيب" :value="$grade->sort_order ?? 0" min="0" />
        </div>

        <div class="flex gap-2">
            <x-primary-button>حفظ</x-primary-button>
            <a href="{{ route('admin.grades.index') }}" class="btn-secondary">إلغاء</a>
        </div>
    </form>
</x-app-layout>
