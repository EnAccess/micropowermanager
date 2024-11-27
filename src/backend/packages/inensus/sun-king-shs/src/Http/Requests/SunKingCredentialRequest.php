<?php

namespace Inensus\SunKingSHS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SunKingCredentialRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'client_id' => ['required'],
            'client_secret' => ['required'],
        ];
    }
}
