<?php

namespace App\Http\Requests;

use App\Services\ImportServices\UserImportItem;
use App\Services\ImportServices\UserRoleItem;
use Illuminate\Foundation\Http\FormRequest;

class UserPermissionImportRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'data' => ['required', 'array', 'list'],
            'data.*.name' => ['required', 'string', 'min:1'],
            'data.*.email' => ['required', 'email'],
            'data.*.password' => ['sometimes', 'nullable', 'string'],
            'data.*.company_id' => ['sometimes', 'nullable', 'integer'],
            'data.*.roles' => ['sometimes', 'nullable', 'array'],
            'data.*.roles.*.name' => ['required_with:data.*.roles', 'string'],
            'data.*.roles.*.permissions' => ['sometimes', 'nullable', 'array'],
            'data.*.roles.*.permissions.*' => ['string'],
            'data.*.all_permissions' => ['sometimes', 'nullable', 'array'],
            'data.*.all_permissions.*' => ['string'],
        ];
    }

    /**
     * @return list<UserImportItem>
     */
    public function items(): array {
        return array_map(fn (array $item): UserImportItem => new UserImportItem(
            name: $item['name'],
            email: $item['email'],
            password: $item['password'] ?? null,
            companyId: $item['company_id'] ?? null,
            roles: array_values(array_map(
                fn (array $role): UserRoleItem => new UserRoleItem(
                    name: $role['name'],
                    permissions: isset($role['permissions']) ? array_values($role['permissions']) : null,
                ),
                $item['roles'] ?? [],
            )),
            allPermissions: array_values($item['all_permissions'] ?? []),
        ), $this->validated('data'));
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array {
        return [
            'data.required' => 'The data field is required.',
            'data.array' => 'The data must be an array.',
            'data.*.name.required' => 'Each user must have a name.',
            'data.*.name.string' => 'User name must be a string.',
            'data.*.email.required' => 'Each user must have an email.',
            'data.*.email.email' => 'Each user must have a valid email address.',
            'data.*.roles.array' => 'Roles must be an array.',
            'data.*.roles.*.name.required_with' => 'Each role must have a name.',
            'data.*.roles.*.permissions.array' => 'Role permissions must be an array.',
            'data.*.all_permissions.array' => 'All permissions must be an array.',
        ];
    }
}
