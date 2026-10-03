<?php

namespace App\Http\Requests\Admin;

use App\Enums\MotivationType;
use App\Support\VideoEmbed;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class MotivationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Route middleware enforces motivations.manage.
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:3000'],
            'media_type' => ['required', Rule::enum(MotivationType::class)],
            'video_url' => ['nullable', 'required_if:media_type,video', 'url:https', 'max:2048'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=6000,max_height=6000'],
            'remove_image' => ['boolean'],
            'publish_date' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($this->filled('video_url') && ! VideoEmbed::isSupported($this->input('video_url'))) {
                $validator->errors()->add('video_url', 'رابط الفيديو غير مدعوم. استخدمي رابط يوتيوب أو فيميو مباشرًا.');
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'العنوان', 'content' => 'النص', 'media_type' => 'النوع', 'video_url' => 'رابط الفيديو',
            'image' => 'الصورة', 'publish_date' => 'تاريخ العرض',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active'), 'remove_image' => $this->boolean('remove_image')]);
    }
}
