<?php

namespace Inensus\SparkMeter\Http\Requests;

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
            'api_secret' => 'required',
        ];
    }
}
