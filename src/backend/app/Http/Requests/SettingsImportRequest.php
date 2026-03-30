<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingsImportRequest extends FormRequest {
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
            'data' => ['required', 'array'],
            'data.0' => ['sometimes', 'array'],
            'data.site_title' => ['sometimes', 'nullable', 'string'],
            'data.company_name' => ['sometimes', 'nullable', 'string'],
            'data.currency' => ['sometimes', 'nullable', 'string'],
            'data.country' => ['sometimes', 'nullable', 'string'],
            'data.language' => ['sometimes', 'nullable', 'string'],
            'data.vat_energy' => ['sometimes', 'nullable', 'numeric'],
            'data.vat_appliance' => ['sometimes', 'nullable', 'numeric'],
            'data.usage_type' => ['sometimes', 'nullable', 'string'],
            'data.sms_gateway_id' => ['sometimes', 'nullable', 'string'],
            'data.created_at' => ['sometimes', 'nullable', 'string'],
            'data.updated_at' => ['sometimes', 'nullable', 'string'],
            'data.0.site_title' => ['sometimes', 'nullable', 'string'],
            'data.0.company_name' => ['sometimes', 'nullable', 'string'],
            'data.0.currency' => ['sometimes', 'nullable', 'string'],
            'data.0.country' => ['sometimes', 'nullable', 'string'],
            'data.0.language' => ['sometimes', 'nullable', 'string'],
            'data.0.vat_energy' => ['sometimes', 'nullable', 'numeric'],
            'data.0.vat_appliance' => ['sometimes', 'nullable', 'numeric'],
            'data.0.usage_type' => ['sometimes', 'nullable', 'string'],
            'data.0.sms_gateway_id' => ['sometimes', 'nullable', 'string'],
            'data.0.created_at' => ['sometimes', 'nullable', 'string'],
            'data.0.updated_at' => ['sometimes', 'nullable', 'string'],
        ];
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
            'data.vat_energy.numeric' => 'VAT energy must be a number.',
            'data.vat_appliance.numeric' => 'VAT appliance must be a number.',
            'data.0.vat_energy.numeric' => 'VAT energy must be a number.',
            'data.0.vat_appliance.numeric' => 'VAT appliance must be a number.',
        ];
    }
}
