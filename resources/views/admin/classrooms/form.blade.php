<x-app-layout area="admin" :title="$classroom->exists ? 'تعديل فصل' : 'إضافة فصل'">
    <x-page-header :title="$classroom->exists ? 'تعديل فصل' : 'إضافة فصل'" />

    <form method="POST" action="{{ $classroom->exists ? route('admin.classes.update', $classroom) : route('admin.classes.store') }}" class="card mt-6 max-w-2xl space-y-5 p-6">
        @csrf
        @if ($classroom->exists) @method('PUT') @endif

        <x-form.select name="grade_id" label="الصف" :options="$grades" :value="$classroom->grade_id" placeholder="اختاري الصف" required />
        <x-form.input name="name" label="اسم الفصل" :value="$classroom->name" hint="مثال: 3/1" required />
        <x-form.input name="sort_order" type="number" label="الترتيب" :value="$classroom->sort_order ?? 0" min="0" />

        <div class="flex gap-2">
            <x-primary-button>حفظ</x-primary-button>
            <a href="{{ route('admin.classes.index') }}" class="btn-secondary">إلغاء</a>
        </div>
    </form>
</x-app-layout>
