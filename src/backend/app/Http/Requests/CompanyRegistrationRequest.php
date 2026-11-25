<?php

namespace App\Http\Requests;

use App\Enums\MpmPlugin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyRegistrationRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'name' => ['required'],
            'address' => ['required'],
            'phone' => ['required'],
            'email' => ['required', 'unique:database_proxies'],
            'plugins' => ['sometimes', 'array'],
            'plugins.*.slug' => ['required', Rule::enum(MpmPlugin::class)],
        ];
    }

    /**
     * @return list<MpmPlugin>
     */
    public function mpmPlugins(): array {
        return array_map(
            fn (array $plugin): MpmPlugin => MpmPlugin::from($plugin['slug']),
            $this->input('plugins', []),
        );
    }
}
