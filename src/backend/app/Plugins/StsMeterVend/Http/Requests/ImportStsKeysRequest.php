<?php

namespace App\Plugins\StsMeterVend\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportStsKeysRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'csv' => ['required', 'file', 'mimes:csv,txt'],
            'manufacturer_id' => ['nullable', 'integer', 'exists:tenant.manufacturers,id'],
            'tariff_id' => ['required_with:manufacturer_id', 'nullable', 'integer', 'exists:tenant.tariffs,id'],
        ];
    }

    public function manufacturerId(): ?int {
        return $this->filled('manufacturer_id') ? $this->integer('manufacturer_id') : null;
    }

    public function tariffId(): ?int {
        return $this->filled('tariff_id') ? $this->integer('tariff_id') : null;
    }
}
