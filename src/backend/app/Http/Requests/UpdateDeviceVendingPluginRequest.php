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
            'vending_plugin_slug' => ['present', 'nullable', Rule::in(resolve(MpmPluginCatalog::class)->slugs())],
        ];
    }

    public function vendingPluginSlug(): ?string {
        return $this->filled('vending_plugin_slug') ? $this->string('vending_plugin_slug')->toString() : null;
    }
}
