<?php

namespace Inensus\SunKingSHS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SunKingCredentialRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array {
        return [
            'client_id' => ['required'],
            'client_secret' => ['required'],
        ];
    }
}
