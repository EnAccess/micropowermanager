<?php

namespace Inensus\CalinSmartMeter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CalinSmartCredentialRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'company_name' => ['required'],
            'user_name' => ['required', Rule::unique('tenant.calin_smart_api_credentials')->ignore($this->id)],
            'password' => ['required'],
            'password_vend' => ['required'],
        ];
    }
}
