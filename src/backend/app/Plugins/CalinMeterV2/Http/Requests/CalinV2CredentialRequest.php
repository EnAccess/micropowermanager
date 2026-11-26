<?php

namespace App\Plugins\CalinMeterV2\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CalinV2CredentialRequest extends FormRequest {
    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'api_url' => ['required', 'url'],
            'company' => ['nullable', 'string'],
            'user_id' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @return array{api_url: string, company: ?string, user_id: string, password: string}
     */
    public function credentials(): array {
        return [
            'api_url' => $this->string('api_url')->toString(),
            'company' => $this->filled('company') ? $this->string('company')->toString() : null,
            'user_id' => $this->string('user_id')->toString(),
            'password' => $this->string('password')->toString(),
        ];
    }
}
