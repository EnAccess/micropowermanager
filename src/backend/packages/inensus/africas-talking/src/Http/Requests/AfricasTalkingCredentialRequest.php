<?php

namespace Inensus\AfricasTalking\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AfricasTalkingCredentialRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array {
        return [
            'api_key' => ['required'],
            'username' => ['required'],
            'short_code' => ['required'],
        ];
    }
}
