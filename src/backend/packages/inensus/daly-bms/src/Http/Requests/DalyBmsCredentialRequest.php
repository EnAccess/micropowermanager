<?php

namespace Inensus\DalyBms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DalyBmsCredentialRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array {
        return [
            'user_name' => ['required'],
            'password' => ['required'],
        ];
    }
}
