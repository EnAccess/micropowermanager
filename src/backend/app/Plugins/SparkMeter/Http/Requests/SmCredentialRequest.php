<?php

namespace App\Plugins\SparkMeter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SmCredentialRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'api_key' => ['required', Rule::unique('tenant.sm_api_credentials')->ignore($this->id)],
            'api_secret' => ['required'],
            // Not Laravel's `url` rule: it rejects `http://host.docker.internal:8765`-shaped
            // values that a local ThunderCloud instance actually uses.
            'thundercloud_url' => ['nullable', 'string', 'min:3'],
            'thundercloud_token' => ['nullable', 'string'],
        ];
    }
}
