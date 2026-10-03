@php($editing = $motivation->exists)

<x-app-layout area="admin" :title="$editing ? 'تعديل دفعة' : 'إضافة دفعة'">
    <x-page-header :title="$editing ? 'تعديل دفعة' : 'إضافة دفعة'" />

    <form method="POST" enctype="multipart/form-data" action="{{ $editing ? route('admin.motivations.update', $motivation) : route('admin.motivations.store') }}"
        class="card mt-6 max-w-2xl space-y-5 p-6" x-data="{ type: @js(old('media_type', $motivation->media_type->value)) }">
        @csrf
        @if ($editing) @method('PUT') @endif

        <x-form.input name="title" label="العنوان" :value="$motivation->title" required hint="مثال: مهمة 15 دقيقة" />
        <x-form.select name="media_type" label="النوع" :value="$motivation->media_type->value" x-model="type"
            :options="collect($types)->mapWithKeys(fn ($t) => [$t->value => $t->label()])" />
        <div>
            <x-input-label for="content" value="النص" />
            <textarea id="content" name="content" rows="4" required class="block w-full rounded-xl border-line bg-white text-ink shadow-sm focus:border-brand-600 focus:ring-brand-600">{{ old('content', $motivation->content) }}</textarea>
            <x-input-error :messages="$errors->get('content')" />
        </div>
        <div x-show="type === 'video'">
            <x-form.input name="video_url" type="url" label="رابط المقطع (يوتيوب أو فيميو)" :value="$motivation->video_url" dir="ltr" class="text-start" />
        </div>
        <div x-show="type === 'image'">
            <x-input-label for="image" value="صورة (JPG/PNG/WebP حتى 2MB، تُحوّل إلى WebP)" />
            @if ($motivation->imageUrl())
                <img src="{{ $motivation->imageUrl() }}" alt="الصورة الحالية" class="mb-2 h-24 rounded-xl object-cover">
                <x-form.checkbox name="remove_image" label="إزالة الصورة الحالية" />
            @endif
            <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="block w-full rounded-xl border border-line bg-white text-sm file:me-3 file:min-h-[44px] file:border-0 file:bg-brand-100 file:px-4 file:font-semibold file:text-brand-800">
            <x-input-error :messages="$errors->get('image')" />
        </div>
        <x-form.input name="publish_date" type="date" label="تاريخ العرض (اختياري)" :value="$motivation->publish_date?->format('Y-m-d')" hint="اتركيه فارغًا ليدخل في التناوب اليومي." />
        <x-form.checkbox name="is_active" label="نشط" :checked="$motivation->is_active" />

        <div class="flex gap-2">
            <x-primary-button>حفظ</x-primary-button>
            <a href="{{ route('admin.motivations.index') }}" class="btn-secondary">إلغاء</a>
        </div>
    </form>
</x-app-layout>
