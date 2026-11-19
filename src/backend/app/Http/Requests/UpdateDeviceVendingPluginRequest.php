<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeviceVendingPluginRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array {
        return [
            'vending_plugin_id' => ['present', 'nullable', 'integer'],
        ];
    }

    public function vendingPluginId(): ?int {
        return $this->filled('vending_plugin_id') ? $this->integer('vending_plugin_id') : null;
    }
}
