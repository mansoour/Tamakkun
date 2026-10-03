<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContentDifficulty;
use App\Enums\ContentSection;
use App\Enums\ContentStage;
use App\Enums\ContentType;
use App\Models\Category;
use App\Models\Chapter;
use App\Models\Topic;
use App\Support\VideoEmbed;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Create and update learning content.
 */
class ContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Route middleware enforces content.manage.
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'body' => ['nullable', 'string', 'max:50000'],
            'section' => ['required', Rule::enum(ContentSection::class)],
            'content_type' => ['required', Rule::enum(ContentType::class)],
            'category_id' => ['nullable', 'required_if:section,quantitative,verbal', 'integer', 'exists:categories,id'],
            'subject_id' => ['nullable', 'required_if:section,tahsili', 'integer', 'exists:subjects,id'],
            'chapter_id' => ['nullable', 'integer', 'exists:chapters,id'],
            'topic_id' => ['nullable', 'integer', 'exists:topics,id'],
            'source_id' => ['nullable', 'integer', 'exists:sources,id'],
            'stage' => ['nullable', Rule::enum(ContentStage::class)],
            'difficulty' => ['nullable', Rule::enum(ContentDifficulty::class)],
            'video_url' => ['nullable', 'required_if:content_type,video', 'url:https', 'max:2048'],
            'external_url' => ['nullable', 'required_if:content_type,link', 'url:https', 'max:2048'],
            'thumbnail' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=6000,max_height=6000'],
            'remove_thumbnail' => ['boolean'],
            'duration_minutes' => ['nullable', 'integer', 'between:1,600'],
            'sort_order' => ['nullable', 'integer', 'between:0,10000'],
            'is_published' => ['boolean'],
            'published_at' => ['nullable', 'date'],
        ];
    }

    /**
     * Cross-field checks that keep the content tree consistent.
     */
    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->filled('video_url') && ! VideoEmbed::isSupported($this->input('video_url'))) {
                $validator->errors()->add('video_url', 'رابط الفيديو غير مدعوم. استخدمي رابط يوتيوب أو فيميو مباشرًا.');
            }

            $section = $this->input('section');

            if ($this->filled('category_id') && in_array($section, ['quantitative', 'verbal'], true)
                && Category::find($this->input('category_id'))?->section->value !== $section) {
                $validator->errors()->add('category_id', 'التصنيف المختار لا يتبع هذا القسم.');
            }

            if ($section === 'tahsili') {
                $chapter = $this->filled('chapter_id') ? Chapter::find($this->input('chapter_id')) : null;

                if ($chapter && $chapter->subject_id !== (int) $this->input('subject_id')) {
                    $validator->errors()->add('chapter_id', 'الباب المختار لا يتبع هذه المادة.');
                }

                if ($this->filled('topic_id') && Topic::find($this->input('topic_id'))?->chapter_id !== $chapter?->id) {
                    $validator->errors()->add('topic_id', 'الموضوع المختار لا يتبع هذا الباب.');
                }
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'العنوان', 'description' => 'الوصف', 'body' => 'نص الدرس', 'section' => 'القسم',
            'content_type' => 'نوع المحتوى', 'category_id' => 'التصنيف', 'subject_id' => 'المادة',
            'chapter_id' => 'الباب', 'topic_id' => 'الموضوع', 'source_id' => 'المصدر', 'stage' => 'المرحلة',
            'difficulty' => 'المستوى', 'video_url' => 'رابط الفيديو', 'external_url' => 'الرابط الخارجي',
            'thumbnail' => 'الصورة المصغرة', 'duration_minutes' => 'المدة بالدقائق', 'sort_order' => 'الترتيب',
            'published_at' => 'تاريخ النشر',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.required_if' => 'اختاري التصنيف لمحتوى القدرات.',
            'subject_id.required_if' => 'اختاري المادة لمحتوى التحصيلي.',
            'video_url.required_if' => 'رابط الفيديو مطلوب لمحتوى من نوع مقطع فيديو.',
            'external_url.required_if' => 'الرابط الخارجي مطلوب لمحتوى من نوع رابط.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_published' => $this->boolean('is_published'),
            'remove_thumbnail' => $this->boolean('remove_thumbnail'),
        ]);
    }
}
