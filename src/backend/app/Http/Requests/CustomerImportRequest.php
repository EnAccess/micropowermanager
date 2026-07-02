<?php

namespace App\Http\Requests;

use App\Services\ImportServices\CustomerImportItem;
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
            'data' => ['required', 'array', 'list'],
            'data.*.title' => ['sometimes', 'nullable', 'string'],
            'data.*.name' => ['required', 'string', 'min:1'],
            'data.*.surname' => ['sometimes', 'nullable', 'string'],
            'data.*.birth_date' => ['sometimes', 'nullable', 'string'],
            'data.*.gender' => ['sometimes', 'nullable', 'string'],
            'data.*.email' => ['sometimes', 'nullable', 'string'],
            'data.*.phone' => ['sometimes', 'nullable', 'string'],
            'data.*.street' => ['sometimes', 'nullable', 'string'],
            'data.*.city' => ['sometimes', 'nullable', 'string'],
            'data.*.devices' => ['sometimes', 'nullable', 'string'],
        ];
    }

    /**
     * @return list<CustomerImportItem>
     */
    public function items(): array {
        return array_map(fn (array $item): CustomerImportItem => new CustomerImportItem(
            name: $item['name'],
            surname: $item['surname'] ?? '',
            title: $item['title'] ?? null,
            birthDate: $item['birth_date'] ?? null,
            gender: $item['gender'] ?? null,
            email: $item['email'] ?? null,
            phone: $item['phone'] ?? null,
            street: $item['street'] ?? null,
            city: $item['city'] ?? null,
            devices: $item['devices'] ?? null,
        ), $this->validated('data'));
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
