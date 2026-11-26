<?php

namespace App\Http\Requests;

use App\Services\MpmPluginCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeviceVendingPluginRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array {
        return [
            'vending_plugin' => ['present', 'nullable', Rule::in(resolve(MpmPluginCatalog::class)->slugs())],
        ];
    }

    public function vendingPlugin(): ?string {
        return $this->filled('vending_plugin') ? $this->string('vending_plugin')->toString() : null;
    }
}
