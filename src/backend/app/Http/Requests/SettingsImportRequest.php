<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingsImportRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'data' => ['required', 'array', 'list', 'size:1'],
            'data.*.site_title' => ['sometimes', 'nullable', 'string'],
            'data.*.company_name' => ['sometimes', 'nullable', 'string'],
            'data.*.currency' => ['sometimes', 'nullable', 'string'],
            'data.*.country' => ['sometimes', 'nullable', 'string'],
            'data.*.language' => ['sometimes', 'nullable', 'string'],
            'data.*.vat_energy' => ['sometimes', 'nullable', 'numeric'],
            'data.*.vat_appliance' => ['sometimes', 'nullable', 'numeric'],
            'data.*.usage_type' => ['sometimes', 'nullable', 'string'],
            'data.*.sms_gateway_id' => ['sometimes', 'nullable', 'string'],
            'data.*.transaction_sms_enabled' => ['sometimes', 'nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array {
        return [
            'data.required' => 'The data field is required.',
            'data.array' => 'The data must be an array.',
            'data.size' => 'The data must contain exactly one settings object.',
            'data.*.vat_energy.numeric' => 'VAT energy must be a number.',
            'data.*.vat_appliance.numeric' => 'VAT appliance must be a number.',
        ];
    }
}
