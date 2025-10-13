<?php

namespace Inensus\ViberMessaging\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ViberCredentialRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array {
        return [
            'api_token' => ['required'],
        ];
    }
}
