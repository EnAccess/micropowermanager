<?php

namespace App\Plugins\WhatsApp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'api_access_token' => ['nullable', 'string'],
            'sender_phone_number' => ['nullable', 'string'],
            'webhook_verify_token' => ['nullable', 'string'],
            'api_version' => ['nullable', 'string'],
            'is_enabled' => ['nullable', 'boolean'],
            'notification_settings' => ['nullable', 'array'],
            'notification_settings.payment_receipts' => ['nullable', 'boolean'],
            'notification_settings.low_balance_warnings' => ['nullable', 'boolean'],
            'notification_settings.token_deliveries' => ['nullable', 'boolean'],
        ];
    }
}
