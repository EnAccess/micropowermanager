<?php

namespace Inensus\CalinMeter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CalinCredentialRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'user_id' => ['required', Rule::unique('tenant.calin_api_credentials')->ignore($this->id)],
            'api_key' => ['required', Rule::unique('tenant.calin_api_credentials')->ignore($this->id)],
        ];
    }
}
