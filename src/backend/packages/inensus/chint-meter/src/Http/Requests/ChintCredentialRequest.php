<?php

namespace Inensus\ChintMeter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChintCredentialRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'user_name' => ['required'],
            'user_password' => ['required'],
        ];
    }
}
