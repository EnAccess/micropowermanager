<?php

namespace Inensus\StronMeter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StronCredentialRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'company_name' => ['required', Rule::unique('tenant.stron_api_credentials')->ignore($this->id)],
            'username' => ['required', Rule::unique('tenant.stron_api_credentials')->ignore($this->id)],
            'password' => 'required',
        ];
    }
}
