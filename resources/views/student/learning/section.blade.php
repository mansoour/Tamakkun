<x-app-layout area="student" :title="$section->label()">
    <x-page-header :title="$section->label()" description="تأسيس ← تدريب ← إتقان ← مراجعة. اختاري المهارة وابدئي من مرحلة التأسيس." />

    <form method="GET" class="card mt-6 grid gap-4 p-4 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
        <x-form.input name="q" label="بحث" :value="request('q')" placeholder="عنوان الدرس أو المقطع" />
        <x-form.select name="stage" label="المرحلة" :value="request('stage')" placeholder="كل المراحل"
            :options="collect($stages)->mapWithKeys(fn ($s) => [$s->value => $s->label()])" />
        <x-form.select name="source_id" label="المصدر" :options="$sources" :value="request('source_id')" placeholder="كل المصادر" />
        <div class="flex gap-2">
            <button class="btn-primary flex-1">عرض</button>
            @if ($filtered)
                <a href="{{ url()->current() }}" class="btn-secondary">إلغاء التصفية</a>
            @endif
        </div>
    </form>

    @if ($total === 0)
        <x-empty-state class="mt-6" :icon="$section->icon()" title="لا يوجد محتوى منشور بعد"
            :description="$filtered ? 'لا توجد نتائج مطابقة. جرّبي تغيير التصفية.' : 'ستضيف الإدارة الدروس والمقاطع قريبًا.'" />
    @else
        <div class="mt-6 space-y-8">
            @foreach ($categories as $category)
                @php($items = $contentsByCategory->get($category->id, collect()))
                @continue($items->isEmpty())
                <section aria-labelledby="cat-{{ $category->id }}">
                    <div class="flex items-baseline justify-between gap-3">
                        <h2 id="cat-{{ $category->id }}" class="text-lg font-semibold text-ink">{{ $category->name }}</h2>
                        <span class="text-sm text-muted">{{ $items->count() }} عنصر</span>
                    </div>
                    @if ($category->description)
                        <p class="mt-1 text-sm text-muted">{{ $category->description }}</p>
                    @endif
                    <div class="mt-3 grid gap-3 md:grid-cols-2">
                        @foreach ($items as $content)
                            <x-content-card :content="$content" :status="$statuses->get($content->id)" />
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</x-app-layout>
