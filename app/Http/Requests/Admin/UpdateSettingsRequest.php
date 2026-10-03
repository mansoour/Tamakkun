<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Only settings that already affect behaviour are editable here; more are
 * added as the features that read them are built.
 */
class UpdateSettingsRequest extends FormRequest
{
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
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['force_password_change' => 'إلزام تغيير كلمة المرور'];
    }

    /**
     * @return array<string, bool>
     */
    public function settings(): array
    {
        return ['force_password_change' => $this->boolean('force_password_change')];
    }
}
