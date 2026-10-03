@php
    $editing = $content->exists;
    $enumValue = fn ($v) => $v instanceof \BackedEnum ? $v->value : $v;
    $options = fn ($cases) => collect($cases)->mapWithKeys(fn ($c) => [$c->value => $c->label()]);
    $selectClass = 'block w-full min-h-[44px] rounded-xl border-line bg-white text-ink shadow-sm focus:border-brand-600 focus:ring-brand-600';
@endphp

<x-app-layout area="admin" :title="$editing ? 'تعديل محتوى' : 'إضافة محتوى'">
    <x-page-header :title="$editing ? 'تعديل محتوى' : 'إضافة محتوى'">
        @if ($editing && $content->content_type === \App\Enums\ContentType::QUIZ)
            <x-slot:actions>
                <a href="{{ route('admin.content.quiz.edit', $content) }}" class="btn-secondary"><x-icon name="clipboard-document-list" /> أسئلة الاختبار</a>
            </x-slot:actions>
        @endif
    </x-page-header>

    <form method="POST" enctype="multipart/form-data"
        action="{{ $editing ? route('admin.content.update', $content) : route('admin.content.store') }}"
        class="mt-6 grid gap-6 lg:grid-cols-3"
        x-data="{
            section: @js((string) old('section', $enumValue($content->section))),
            type: @js((string) old('content_type', $enumValue($content->content_type))),
            categoryId: @js((string) old('category_id', $content->category_id)),
            subjectId: @js((string) old('subject_id', $content->subject_id)),
            chapterId: @js((string) old('chapter_id', $content->chapter_id)),
            topicId: @js((string) old('topic_id', $content->topic_id)),
            categories: @js($categoriesBySection),
            chapters: @js($chaptersBySubject),
            topics: @js($topicsByChapter),
        }">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="card space-y-5 p-6 lg:col-span-2">
            <x-form.input name="title" label="العنوان" :value="$content->title" required />
            <div>
                <x-input-label for="description" value="وصف مختصر" />
                <textarea id="description" name="description" rows="2" class="{{ $selectClass }}">{{ old('description', $content->description) }}</textarea>
                <x-input-error :messages="$errors->get('description')" />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.select name="section" label="القسم" :options="$options($sections)" :value="$enumValue($content->section)" x-model="section" required />
                <x-form.select name="content_type" label="نوع المحتوى" :options="$options($types)" :value="$enumValue($content->content_type)" x-model="type" required />
            </div>

            {{-- Qudurat: category --}}
            <div x-show="section !== 'tahsili'">
                <x-input-label for="category_id" value="التصنيف" />
                <select id="category_id" name="category_id" x-model="categoryId" :disabled="section === 'tahsili'" class="{{ $selectClass }}">
                    <option value="">اختاري التصنيف</option>
                    <template x-for="c in (categories[section] || [])" :key="c.id">
                        <option :value="String(c.id)" x-text="c.label" :selected="String(c.id) === categoryId"></option>
                    </template>
                </select>
                <x-input-error :messages="$errors->get('category_id')" />
            </div>

            {{-- Tahsili: subject → chapter → topic --}}
            <div x-show="section === 'tahsili'" x-cloak class="grid gap-5 sm:grid-cols-3">
                <div>
                    <x-input-label for="subject_id" value="المادة" />
                    <select id="subject_id" name="subject_id" x-model="subjectId" x-on:change="chapterId = ''; topicId = ''" :disabled="section !== 'tahsili'" class="{{ $selectClass }}">
                        <option value="">اختاري المادة</option>
                        @foreach ($subjects as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('subject_id')" />
                </div>
                <div>
                    <x-input-label for="chapter_id" value="الباب (اختياري)" />
                    <select id="chapter_id" name="chapter_id" x-model="chapterId" x-on:change="topicId = ''" :disabled="section !== 'tahsili'" class="{{ $selectClass }}">
                        <option value="">بدون باب</option>
                        <template x-for="c in (chapters[subjectId] || [])" :key="c.id">
                            <option :value="String(c.id)" x-text="c.label" :selected="String(c.id) === chapterId"></option>
                        </template>
                    </select>
                    <x-input-error :messages="$errors->get('chapter_id')" />
                </div>
                <div>
                    <x-input-label for="topic_id" value="الموضوع (اختياري)" />
                    <select id="topic_id" name="topic_id" x-model="topicId" :disabled="section !== 'tahsili'" class="{{ $selectClass }}">
                        <option value="">بدون موضوع</option>
                        <template x-for="t in (topics[chapterId] || [])" :key="t.id">
                            <option :value="String(t.id)" x-text="t.label" :selected="String(t.id) === topicId"></option>
                        </template>
                    </select>
                    <x-input-error :messages="$errors->get('topic_id')" />
                </div>
            </div>

            <div x-show="type === 'video'">
                <x-form.input name="video_url" type="url" label="رابط الفيديو (يوتيوب أو فيميو)" :value="$content->video_url" dir="ltr" class="text-start"
                    hint="الصقي رابط المقطع العادي من موقعه الرسمي. لا يُقبل كود iframe." />
            </div>
            <x-form.input name="external_url" type="url" label="رابط خارجي (اختياري لغير الروابط)" :value="$content->external_url" dir="ltr" class="text-start"
                hint="رابط https لصفحة رسمية أو مصدر مصرَّح به فقط." />

            <div>
                <x-input-label for="body" value="نص الدرس (اختياري)" />
                <textarea id="body" name="body" rows="8" class="{{ $selectClass }}">{{ old('body', $content->body) }}</textarea>
                <p class="mt-1.5 text-xs text-muted">نص عادي. تُحفظ الأسطر الجديدة كما هي. لا تنسخي محتوى مدفوعًا أو محميًا.</p>
                <x-input-error :messages="$errors->get('body')" />
            </div>
        </div>

        <div class="space-y-6">
            <div class="card space-y-5 p-6">
                <x-form.select name="source_id" label="المصدر" :options="$sources" :value="$content->source_id" placeholder="بدون مصدر" />
                <x-form.select name="stage" label="المرحلة" :options="$options($stages)" :value="$enumValue($content->stage)" placeholder="—" />
                <x-form.select name="difficulty" label="المستوى" :options="$options($difficulties)" :value="$enumValue($content->difficulty)" placeholder="—" />
                <div class="grid grid-cols-2 gap-4">
                    <x-form.input name="duration_minutes" type="number" label="المدة (دقيقة)" :value="$content->durationMinutes()" min="1" />
                    <x-form.input name="sort_order" type="number" label="الترتيب" :value="$content->sort_order" min="0" />
                </div>
            </div>

            <div class="card space-y-4 p-6">
                <div>
                    <x-input-label for="thumbnail" value="صورة مصغرة (JPG/PNG/WebP حتى 2MB)" />
                    @if ($content->thumbnailUrl())
                        <img src="{{ $content->thumbnailUrl() }}" alt="الصورة الحالية" class="mb-2 h-24 rounded-xl object-cover">
                        <x-form.checkbox name="remove_thumbnail" label="إزالة الصورة الحالية" />
                    @endif
                    <input id="thumbnail" name="thumbnail" type="file" accept="image/jpeg,image/png,image/webp"
                        class="block w-full rounded-xl border border-line bg-white text-sm file:me-3 file:min-h-[44px] file:border-0 file:bg-brand-100 file:px-4 file:font-semibold file:text-brand-800">
                    <p class="mt-1.5 text-xs text-muted">تُحوَّل تلقائيًا إلى WebP.</p>
                    <x-input-error :messages="$errors->get('thumbnail')" />
                </div>
            </div>

            <div class="card space-y-4 p-6">
                <x-form.checkbox name="is_published" label="منشور للطالبات" :checked="$content->is_published" />
                <x-form.input name="published_at" type="datetime-local" label="تاريخ النشر (اختياري)" :value="$content->published_at?->format('Y-m-d\TH:i')"
                    hint="اتركيه فارغًا للنشر فورًا، أو حدّدي موعدًا لاحقًا." />
                <div class="flex gap-2">
                    <x-primary-button>حفظ</x-primary-button>
                    <a href="{{ route('admin.content.index') }}" class="btn-secondary">إلغاء</a>
                </div>
            </div>
        </div>
    </form>
</x-app-layout>
