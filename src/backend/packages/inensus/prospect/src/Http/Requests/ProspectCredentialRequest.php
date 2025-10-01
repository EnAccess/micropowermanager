<?php

namespace Inensus\Prospect\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProspectCredentialRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'api_url' => ['required', 'url'],
            'api_token' => ['required'],
        ];
    }
}
