<?php

namespace App\Plugins\CalinMeterV2\Http\Requests;

use App\Plugins\CalinMeterV2\Enums\VendBy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'authorization_password' => ['nullable', 'string'],
            'vend_by' => ['sometimes', 'required', Rule::enum(VendBy::class)],
        ];
    }

    /**
     * @return array{api_url: string, company: ?string, user_id: string, password: string, authorization_password: ?string, vend_by: string}
     */
    public function credentials(): array {
        return [
            'api_url' => $this->string('api_url')->toString(),
            'company' => $this->filled('company') ? $this->string('company')->toString() : null,
            'user_id' => $this->string('user_id')->toString(),
            'password' => $this->string('password')->toString(),
            'authorization_password' => $this->filled('authorization_password') ? $this->string('authorization_password')->toString() : null,
            'vend_by' => $this->enum('vend_by', VendBy::class, VendBy::Energy)->value,
        ];
    }
}
