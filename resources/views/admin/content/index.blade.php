<x-app-layout area="admin" title="المحتوى التعليمي">
    <x-page-header title="المحتوى التعليمي" description="الدروس والمقاطع والروابط للقدرات (كمي ولفظي) والتحصيلي.">
        <x-slot:actions>
            <a href="{{ route('admin.content.create') }}" class="btn-primary"><x-icon name="book-open" /> إضافة محتوى</a>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="card mt-6 grid gap-4 p-4 sm:grid-cols-2 lg:grid-cols-3 lg:items-end">
        <x-form.input name="q" label="بحث بالعنوان" :value="request('q')" />
        <x-form.select name="section" label="القسم" :value="request('section')" placeholder="كل الأقسام"
            :options="collect(\App\Enums\ContentSection::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])" />
        <x-form.select name="status" label="الحالة" :value="request('status')" placeholder="الكل"
            :options="['published' => 'منشور', 'draft' => 'مسودة', 'archived' => 'مؤرشف']" />
        <x-form.select name="source_id" label="المصدر" :options="$sources" :value="request('source_id')" placeholder="كل المصادر" />
        <x-form.select name="stage" label="المرحلة" :value="request('stage')" placeholder="كل المراحل"
            :options="collect(\App\Enums\ContentStage::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])" />
        <div class="flex gap-2">
            <div class="flex-1"><x-form.select name="subject_id" label="مادة التحصيلي" :options="$subjects" :value="request('subject_id')" placeholder="الكل" /></div>
            <button class="btn-secondary self-end">تصفية</button>
        </div>
    </form>

    <div class="mt-6">
        @if ($contents->isEmpty())
            <x-empty-state icon="book-open" title="لا يوجد محتوى مطابق" description="أضيفي أول درس أو مقطع، أو غيّري عوامل التصفية." />
        @else
            <x-table>
                <x-slot:head>
                    <th scope="col" class="px-4 py-3 text-start">العنوان</th>
                    <th scope="col" class="px-4 py-3 text-start">القسم</th>
                    <th scope="col" class="px-4 py-3 text-start">التصنيف / المادة</th>
                    <th scope="col" class="px-4 py-3 text-start">النوع</th>
                    <th scope="col" class="px-4 py-3 text-start">الحالة</th>
                    <th scope="col" class="px-4 py-3"><span class="sr-only">إجراءات</span></th>
                </x-slot:head>
                @foreach ($contents as $content)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $content->title }}</p>
                            @if ($content->source) <p class="text-xs text-muted">{{ $content->source->name }}</p> @endif
                        </td>
                        <td class="px-4 py-3">{{ $content->section->label() }}</td>
                        <td class="px-4 py-3">{{ $content->category?->name ?? $content->subject?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $content->content_type->label() }}</td>
                        <td class="px-4 py-3">
                            @if ($content->archived_at)
                                <x-badge color="gray">مؤرشف</x-badge>
                            @elseif ($content->isVisible())
                                <x-badge color="success">منشور</x-badge>
                            @elseif ($content->is_published)
                                <x-badge color="brand">مجدول {{ $content->published_at?->format('Y-m-d') }}</x-badge>
                            @else
                                <x-badge color="warning">مسودة</x-badge>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap justify-end gap-1">
                                @if ($content->content_type === \App\Enums\ContentType::QUIZ)
                                    <a href="{{ route('admin.content.quiz.edit', $content) }}" class="btn-ghost min-h-[40px] px-3">الأسئلة</a>
                                @endif
                                <a href="{{ route('admin.content.edit', $content) }}" class="btn-ghost min-h-[40px] px-3">تعديل</a>
                                @foreach (array_filter([
                                    ! $content->archived_at && ! $content->is_published ? ['publish', 'نشر'] : null,
                                    ! $content->archived_at && $content->is_published ? ['unpublish', 'إلغاء النشر'] : null,
                                    ! $content->archived_at ? ['archive', 'أرشفة'] : null,
                                    $content->archived_at ? ['restore', 'استعادة'] : null,
                                ]) as [$action, $label])
                                    <form method="POST" action="{{ route('admin.content.'.$action, $content) }}">
                                        @csrf
                                        <button class="btn-ghost min-h-[40px] px-3">{{ $label }}</button>
                                    </form>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>
            {{ $contents->links() }}
        @endif
    </div>
</x-app-layout>
