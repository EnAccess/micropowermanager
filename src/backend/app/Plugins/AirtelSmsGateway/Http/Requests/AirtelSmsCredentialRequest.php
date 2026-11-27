<?php

namespace App\Plugins\AirtelSmsGateway\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AirtelSmsCredentialRequest extends FormRequest {
    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'host' => ['required', 'string'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'system_id' => ['required', 'string', 'max:15'],
            'password' => ['required', 'string', 'max:8'],
            'source_address' => ['required', 'string', 'max:11'],
        ];
    }
}
