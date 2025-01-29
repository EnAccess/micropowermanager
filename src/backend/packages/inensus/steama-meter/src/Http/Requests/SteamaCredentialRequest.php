<?php

namespace Inensus\SteamaMeter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SteamaCredentialRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'username' => ['required', Rule::unique('tenant.steama_credentials')->ignore($this->id)],
            'password' => 'required',
        ];
    }
}
