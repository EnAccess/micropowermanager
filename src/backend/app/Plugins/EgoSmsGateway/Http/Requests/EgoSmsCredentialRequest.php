<?php

namespace App\Plugins\EgoSmsGateway\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EgoSmsCredentialRequest extends FormRequest {
    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'sender_id' => ['required', 'string', 'max:11'],
        ];
    }
}
