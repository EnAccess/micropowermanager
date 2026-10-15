<?php

namespace App\Plugins\WhatsApp\Http\Requests;

use App\Plugins\WhatsApp\Services\WhatsAppCredentialService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class WhatsAppCredentialRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'id' => ['nullable', 'integer'],
            'phone_number_id' => ['nullable', 'string'],
            'whatsapp_business_account_id' => ['nullable', 'string'],
            'api_access_token' => $this->secretRules(),
            'sender_phone_number' => ['nullable', 'string'],
            'webhook_verify_token' => $this->secretRules(),
            'meta_app_secret' => $this->secretRules(),
            'api_version' => ['nullable', 'string'],
            'is_enabled' => ['nullable', 'boolean'],
            'notification_settings' => ['nullable', 'array'],
            'notification_settings.payment_receipts' => ['nullable', 'boolean'],
            'notification_settings.low_balance_warnings' => ['nullable', 'boolean'],
            'notification_settings.token_deliveries' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void {
        // Global middleware normalizes empty strings to null; clearing must be explicit.
        if (!$this->isJson() && !str_starts_with($this->header('Content-Type', ''), 'application/x-www-form-urlencoded')) {
            foreach (WhatsAppCredentialService::SECRET_FIELDS as $field) {
                if (array_key_exists($field, $this->all())) {
                    throw ValidationException::withMessages([$field => 'Use JSON or URL-encoded input for secret updates.']);
                }
            }
        }
        $raw = $this->getContent();
        $values = $this->isJson() ? json_decode($raw, true) : null;
        if (!$this->isJson()) {
            parse_str($raw, $values);
        }
        if (!is_array($values)) {
            return;
        }
        foreach (WhatsAppCredentialService::SECRET_FIELDS as $field) {
            if (array_key_exists($field, $values)) {
                $this->merge([$field => $values[$field]]);
            }
        }
    }

    /** @return array<int, mixed> */
    private function secretRules(): array {
        return ['nullable', 'string', function (string $attribute, mixed $value, \Closure $fail): void {
            if (!WhatsAppCredentialService::isValidSecret($value)) {
                $fail('Provide a replacement secret or explicit null; omit unchanged secrets.');
            }
        }];
    }
}
