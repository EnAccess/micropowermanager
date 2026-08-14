<?php

declare(strict_types=1);

namespace App\Plugins\FlutterwavePaymentProvider\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCredentialRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * The four secret fields are write-only: the resource never returns the
     * stored values, so the form re-submits them blank unless the operator
     * actually retypes them. They're nullable here for that reason — the
     * service treats a blank field as "keep the existing value" and throws
     * if the credential ends up without a required value after the merge
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array {
        return [
            'secret_key' => ['nullable', 'string', 'min:3'],
            'public_key' => ['nullable', 'string', 'min:3'],
            'encryption_key' => ['nullable', 'string', 'min:3'],
            'webhook_secret_hash' => ['nullable', 'string', 'min:3'],
            'callback_url' => ['required', 'url'],
            'merchant_name' => ['required', 'string', 'min:2'],
            'merchant_email' => ['required', 'email'],
            'environment' => ['required', 'in:test,live'],
        ];
    }

    public function messages(): array {
        return [
            'secret_key.min' => 'The secret key must be at least 3 characters long.',
            'public_key.min' => 'The public key must be at least 3 characters long.',
            'encryption_key.min' => 'The encryption key must be at least 3 characters long.',
            'webhook_secret_hash.min' => 'The webhook secret hash must be at least 3 characters long.',
            'callback_url.required' => 'The callback URL is required.',
            'callback_url.url' => 'The callback URL must be a valid URL.',
            'merchant_name.required' => 'The merchant name is required.',
            'merchant_name.min' => 'The merchant name must be at least 2 characters long.',
            'merchant_email.required' => 'The merchant email is required.',
            'merchant_email.email' => 'The merchant email must be a valid email address.',
            'environment.required' => 'The environment is required.',
            'environment.in' => 'The environment must be either "test" or "live".',
        ];
    }
}
