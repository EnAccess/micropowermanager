<?php

namespace Inensus\MicroStarMeter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MicroStarCredentialRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array {
        return [
            'api_url' => ['required'],
            'certificate_password' => ['required'],
        ];
    }
}
