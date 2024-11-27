<?php

namespace Inensus\ViberMessaging\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ViberCredentialRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'api_token' => ['required'],
        ];
    }
}
