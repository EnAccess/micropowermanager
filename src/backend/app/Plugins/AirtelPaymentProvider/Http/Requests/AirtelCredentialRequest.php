<?php

namespace App\Plugins\AirtelPaymentProvider\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AirtelCredentialRequest extends FormRequest {
    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'jwt_secret' => ['required', 'string'],
            'phone_prefix' => ['required', 'string', 'regex:/^\+\d{1,4}$/'],
        ];
    }
}
