<?php

namespace Inensus\TextbeeSmsGateway\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TextbeeCredentialRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'api_key' => ['required', 'string'],
            'device_id' => ['required', 'string'],
        ];
    }
}
