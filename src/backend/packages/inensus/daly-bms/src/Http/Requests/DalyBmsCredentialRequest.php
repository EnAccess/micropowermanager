<?php

namespace Inensus\DalyBms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DalyBmsCredentialRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'user_name' => ['required'],
            'password' => ['required'],
        ];
    }
}
