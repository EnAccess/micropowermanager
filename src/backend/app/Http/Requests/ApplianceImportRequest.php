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
            'data' => ['required', 'array'],
            'data.*.appliance_name' => ['required', 'string', 'min:1'],
            'data.*.appliance_type' => ['sometimes', 'nullable', 'string'],
            'data.*.price' => ['sometimes', 'nullable'],
            'data.*.currency' => ['sometimes', 'nullable', 'string'],
            'data.*.total_sold' => ['sometimes', 'nullable', 'integer'],
            'data.*.payment_plans' => ['sometimes', 'nullable', 'array'],
            'data.*.payment_plans.*.total_cost' => ['sometimes', 'nullable'],
            'data.*.payment_plans.*.rate_count' => ['sometimes', 'nullable', 'integer'],
            'data.*.payment_plans.*.down_payment' => ['sometimes', 'nullable'],
            'data.*.created_at' => ['sometimes', 'nullable', 'string'],
            'data.*.updated_at' => ['sometimes', 'nullable', 'string'],
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
