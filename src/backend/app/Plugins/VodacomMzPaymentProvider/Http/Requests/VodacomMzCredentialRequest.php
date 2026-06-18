<?php

namespace App\Plugins\VodacomMzPaymentProvider\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VodacomMzCredentialRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'api_key' => ['required', 'string'],
            'public_key' => ['required', 'string'],
            'service_provider_code' => ['required', 'string'],
            'live' => ['required', 'boolean'],
        ];
    }
}
