<?php

namespace App\Http\Requests;

use App\Services\MpmPluginCatalog;
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
            'plugins.*.slug' => ['required', Rule::in(resolve(MpmPluginCatalog::class)->slugs())],
        ];
    }

    /**
     * @return list<string> plugin slugs
     */
    public function mpmPlugins(): array {
        return array_column($this->input('plugins', []), 'slug');
    }
}
