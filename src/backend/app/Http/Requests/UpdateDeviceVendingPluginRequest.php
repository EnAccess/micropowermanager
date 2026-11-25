<?php

namespace App\Http\Requests;

use App\Enums\MpmPlugin;
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
            'vending_plugin' => ['present', 'nullable', Rule::enum(MpmPlugin::class)],
        ];
    }

    public function vendingPlugin(): ?MpmPlugin {
        return $this->enum('vending_plugin', MpmPlugin::class);
    }
}
