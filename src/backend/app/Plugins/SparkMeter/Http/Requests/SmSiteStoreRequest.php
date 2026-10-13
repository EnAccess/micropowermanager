<?php

namespace App\Plugins\SparkMeter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SmSiteStoreRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Uniqueness of thundercloud_url is checked in SiteService::store() instead of here: the
     * stored value is normalized (ending in /api/v0, matching SiteService::sync()'s existing
     * convention for Koios-discovered URLs), and a plain `unique:` rule here would compare the
     * raw operator-typed URL against that normalized form and never match.
     *
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'name' => ['required', 'string', 'max:255'],
            'thundercloud_url' => ['required', 'url'],
            'thundercloud_token' => ['required', 'string'],
        ];
    }
}
