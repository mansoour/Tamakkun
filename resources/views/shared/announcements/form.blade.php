<x-app-layout :area="$area" title="إعلان جديد">
    <x-page-header title="إعلان جديد" />

    <form method="POST" action="{{ route($area.'.announcements.store') }}" class="card mt-6 max-w-2xl space-y-5 p-6"
        x-data="{ audience: @js(old('audience', $audiences[0]->value)) }">
        @csrf
        <x-form.input name="title" label="العنوان" required />
        <div>
            <x-input-label for="body" value="النص" />
            <textarea id="body" name="body" rows="4" required class="block w-full rounded-xl border-line bg-white text-ink shadow-sm focus:border-brand-600 focus:ring-brand-600">{{ old('body') }}</textarea>
            <x-input-error :messages="$errors->get('body')" />
        </div>

        <x-form.select name="audience" label="إلى" x-model="audience" :value="$audiences[0]->value"
            :options="collect($audiences)->mapWithKeys(fn ($a) => [$a->value => $a->label()])" />

        <div x-show="audience === 'classroom'">
            <x-form.select name="target_id" id="target_classroom" label="الفصل" :options="$classrooms" placeholder="اختاري الفصل"
                x-bind:disabled="audience !== 'classroom'" />
        </div>
        <div x-show="audience === 'student'">
            <x-form.select name="target_id" id="target_student" label="الطالبة" :options="$students" placeholder="اختاري الطالبة"
                x-bind:disabled="audience !== 'student'" />
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.input name="starts_at" type="datetime-local" label="بداية العرض (اختياري)" hint="اتركيه فارغًا للإرسال الآن." />
            <x-form.input name="ends_at" type="datetime-local" label="نهاية العرض (اختياري)" />
        </div>

        <div class="flex gap-2">
            <x-primary-button>إرسال</x-primary-button>
            <a href="{{ route($area.'.announcements.index') }}" class="btn-secondary">إلغاء</a>
        </div>
    </form>
</x-app-layout>
