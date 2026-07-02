<?php

namespace App\Http\Requests;

use App\Services\ImportServices\ApplianceImportItem;
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
     * @return list<ApplianceImportItem>
     */
    public function items(): array {
        return array_map(fn (array $item): ApplianceImportItem => new ApplianceImportItem(
            applianceName: $item['appliance_name'],
            applianceType: $item['appliance_type'] ?? null,
            price: $this->parsePrice($item['price'] ?? 0),
        ), $this->validated('data'));
    }

    /**
     * Export files carry prices as display strings ("1,500") as well as plain numbers.
     */
    private function parsePrice(mixed $price): int {
        if (is_string($price)) {
            return (int) str_replace([',', ' '], '', $price);
        }

        return is_numeric($price) ? (int) $price : 0;
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
