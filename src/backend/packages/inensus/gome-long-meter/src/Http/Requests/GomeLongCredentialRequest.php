<?php

namespace Inensus\GomeLongMeter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GomeLongCredentialRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'user_id' => ['required'],
            'user_password' => ['required'],
        ];
    }
}
