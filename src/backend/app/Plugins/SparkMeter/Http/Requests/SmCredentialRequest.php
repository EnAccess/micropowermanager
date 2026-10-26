<?php

namespace App\Plugins\SparkMeter\Http\Requests;

use App\Plugins\SparkMeter\Models\SmCredential;
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
        $credential = SmCredential::query()->first();

        return [
            'api_url' => ['required', 'url'],
            'api_key' => [Rule::requiredIf(blank($credential?->api_key)), 'nullable', 'string'],
            'api_secret' => [Rule::requiredIf(blank($credential?->api_secret)), 'nullable', 'string'],
        ];
    }
}
