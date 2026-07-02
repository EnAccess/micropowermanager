<?php

namespace App\Http\Requests;

use App\Services\ImportServices\SettingsImportItem;
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
     * @return list<SettingsImportItem>
     */
    public function items(): array {
        return array_map(fn (array $item): SettingsImportItem => new SettingsImportItem(
            siteTitle: $item['site_title'] ?? null,
            companyName: $item['company_name'] ?? null,
            currency: $item['currency'] ?? null,
            country: $item['country'] ?? null,
            language: $item['language'] ?? null,
            vatEnergy: isset($item['vat_energy']) ? (float) $item['vat_energy'] : null,
            vatAppliance: isset($item['vat_appliance']) ? (float) $item['vat_appliance'] : null,
            usageType: $item['usage_type'] ?? null,
            smsGatewayId: $item['sms_gateway_id'] ?? null,
            transactionSmsEnabled: isset($item['transaction_sms_enabled']) ? (bool) $item['transaction_sms_enabled'] : null,
        ), $this->validated('data'));
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
