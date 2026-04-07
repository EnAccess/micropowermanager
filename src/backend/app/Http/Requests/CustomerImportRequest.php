<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerImportRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'data' => ['required', 'array'],
            'data.*.title' => ['sometimes', 'nullable', 'string'],
            'data.*.name' => ['required', 'string', 'min:1'],
            'data.*.surname' => ['sometimes', 'nullable', 'string'],
            'data.*.birth_date' => ['sometimes', 'nullable', 'string'],
            'data.*.gender' => ['sometimes', 'nullable', 'string'],
            'data.*.email' => ['sometimes', 'nullable', 'string'],
            'data.*.phone' => ['sometimes', 'nullable', 'string'],
            'data.*.city' => ['sometimes', 'nullable', 'string'],
            'data.*.devices' => ['sometimes', 'nullable', 'string'],
            'data.*.agent' => ['sometimes', 'nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array {
        return [
            'data.required' => 'The data field is required.',
            'data.array' => 'The data must be an array.',
            'data.*.name.required' => 'Each customer must have a name.',
            'data.*.name.string' => 'Customer name must be a string.',
        ];
    }
}
