<?php

namespace App\Plugins\AfricasTalking\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AfricasTalkingCredentialRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'api_key' => ['required'],
            'username' => ['required'],
            'short_code' => ['required'],
        ];
    }
}
