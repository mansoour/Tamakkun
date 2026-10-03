<x-app-layout area="admin" :title="$year->exists ? 'تعديل عام دراسي' : 'إضافة عام دراسي'">
    <x-page-header :title="$year->exists ? 'تعديل عام دراسي' : 'إضافة عام دراسي'" />

    <form method="POST" action="{{ $year->exists ? route('admin.academic-years.update', $year) : route('admin.academic-years.store') }}" class="card mt-6 max-w-2xl space-y-5 p-6">
        @csrf
        @if ($year->exists) @method('PUT') @endif

        <x-form.select name="school_id" label="المدرسة" :options="$schools" :value="$year->school_id" placeholder="اختاري المدرسة" required />
        <x-form.input name="name" label="العام الدراسي" :value="$year->name" hint="مثال: 1447–1448 هـ" required />
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.input name="starts_on" type="date" label="تاريخ البداية" :value="$year->starts_on?->format('Y-m-d')" />
            <x-form.input name="ends_on" type="date" label="تاريخ النهاية" :value="$year->ends_on?->format('Y-m-d')" />
        </div>
        <x-form.checkbox name="is_current" label="العام الدراسي الحالي" :checked="$year->is_current" hint="يُلغى تحديد العام الحالي السابق لنفس المدرسة تلقائيًا." />

        <div class="flex gap-2">
            <x-primary-button>حفظ</x-primary-button>
            <a href="{{ route('admin.academic-years.index') }}" class="btn-secondary">إلغاء</a>
        </div>
    </form>
</x-app-layout>
