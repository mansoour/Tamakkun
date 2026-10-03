<?php

namespace App\Http\Requests\Admin;

use App\Enums\PermissionName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Route middleware enforces roles.manage.
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::enum(PermissionName::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['permissions' => 'الصلاحيات', 'permissions.*' => 'الصلاحية'];
    }

    /**
     * @return list<string>
     */
    public function permissionNames(): array
    {
        return array_values($this->validated('permissions', []));
    }
}
