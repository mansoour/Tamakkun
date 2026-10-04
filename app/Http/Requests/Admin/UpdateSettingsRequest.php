<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Admin-editable platform settings.
 *
 * Checkboxes are always submitted by the form (a missing box means "off").
 * Value fields are `sometimes`: only the ones present in the request are
 * saved, so a partial form never wipes other settings.
 */
class UpdateSettingsRequest extends FormRequest
{
    /**
     * Settings stored as integers.
     */
    private const INTEGERS = [
        'weekly_content_goal', 'default_target_score', 'inactivity_days',
        'upcoming_exam_alert_days', 'low_activity_threshold',
    ];

    /**
     * Settings stored as text; an empty value is stored as null.
     */
    private const TEXTS = [
        'platform_name', 'tagline', 'support_email', 'support_phone',
        'privacy_url', 'terms_url', 'about_text', 'privacy_text', 'terms_text',
        'supervisor_name', 'supervisor_title', 'student_registration',
    ];

    public function authorize(): bool
    {
        return true; // Route middleware enforces settings.manage.
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'force_password_change' => ['required', 'boolean'],
            'enable_gamification' => ['boolean'],
            'email_notifications' => ['boolean'],

            'platform_name' => ['sometimes', 'required', 'string', 'max:40'],
            'tagline' => ['sometimes', 'nullable', 'string', 'max:120'],

            'weekly_content_goal' => ['sometimes', 'required', 'integer', 'between:1,50'],
            'default_target_score' => ['sometimes', 'required', 'integer', 'between:1,100'],
            'inactivity_days' => ['sometimes', 'required', 'integer', 'between:1,60'],
            'upcoming_exam_alert_days' => ['sometimes', 'required', 'integer', 'between:1,90'],
            'low_activity_threshold' => ['sometimes', 'required', 'integer', 'between:0,50'],

            'support_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'support_phone' => ['sometimes', 'nullable', 'string', 'max:20', 'regex:/^\+?[0-9 ]{7,19}$/'],

            'privacy_url' => ['sometimes', 'nullable', 'url:https', 'max:500'],
            'terms_url' => ['sometimes', 'nullable', 'url:https', 'max:500'],
            'about_text' => ['sometimes', 'nullable', 'string', 'max:20000'],
            'privacy_text' => ['sometimes', 'nullable', 'string', 'max:20000'],
            'terms_text' => ['sometimes', 'nullable', 'string', 'max:20000'],

            'supervisor_name' => ['sometimes', 'nullable', 'string', 'max:80'],
            'supervisor_title' => ['sometimes', 'nullable', 'string', 'max:80'],
            'student_registration' => ['sometimes', 'required', 'in:open,approval,closed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'force_password_change' => 'إلزام تغيير كلمة المرور',
            'platform_name' => 'اسم المنصة',
            'tagline' => 'العبارة التعريفية',
            'weekly_content_goal' => 'هدف الإنجاز الأسبوعي',
            'default_target_score' => 'الدرجة المستهدفة الافتراضية',
            'inactivity_days' => 'أيام عدم النشاط',
            'upcoming_exam_alert_days' => 'أيام تنبيه الاختبار القادم',
            'low_activity_threshold' => 'حد النشاط المنخفض',
            'support_email' => 'بريد الدعم',
            'support_phone' => 'هاتف الدعم',
            'privacy_url' => 'رابط سياسة الخصوصية',
            'terms_url' => 'رابط الشروط والأحكام',
            'about_text' => 'نص صفحة عن المنصة',
            'privacy_text' => 'نص سياسة الخصوصية',
            'terms_text' => 'نص الشروط والأحكام',
            'supervisor_name' => 'اسم المشرفة',
            'supervisor_title' => 'صفة الإشراف',
            'student_registration' => 'تسجيل الطالبات',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'support_phone.regex' => 'رقم هاتف الدعم يقبل الأرقام والمسافات وعلامة + في أوله فقط.',
            'privacy_url.url' => 'يجب أن يبدأ رابط سياسة الخصوصية بـ https://',
            'terms_url.url' => 'يجب أن يبدأ رابط الشروط والأحكام بـ https://',
        ];
    }

    /**
     * Validated values ready for SettingsService::setMany().
     *
     * @return array<string, bool|int|string|null>
     */
    public function settings(): array
    {
        $values = [
            'force_password_change' => $this->boolean('force_password_change'),
            'enable_gamification' => $this->boolean('enable_gamification'),
            'email_notifications' => $this->boolean('email_notifications'),
        ];

        foreach (self::INTEGERS as $key) {
            if ($this->has($key)) {
                $values[$key] = $this->integer($key);
            }
        }

        foreach (self::TEXTS as $key) {
            if ($this->has($key)) {
                $text = trim((string) $this->input($key));
                $values[$key] = $text === '' ? null : $text;
            }
        }

        return $values;
    }
}
