<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplianceImportRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'data' => ['required', 'array', 'list'],
            'data.*.appliance_name' => ['required', 'string', 'min:1'],
            'data.*.appliance_type' => ['sometimes', 'nullable', 'string'],
            'data.*.price' => ['sometimes', 'nullable'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array {
        return [
            'data.required' => 'The data field is required.',
            'data.array' => 'The data must be an array.',
            'data.*.appliance_name.required' => 'Each appliance must have a name.',
            'data.*.appliance_name.string' => 'Appliance name must be a string.',
        ];
    }
}
