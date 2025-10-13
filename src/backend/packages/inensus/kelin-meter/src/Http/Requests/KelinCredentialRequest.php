<?php

namespace Inensus\KelinMeter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class KelinCredentialRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array {
        return [
            'username' => ['required'],
            'password' => 'required',
        ];
    }
}
