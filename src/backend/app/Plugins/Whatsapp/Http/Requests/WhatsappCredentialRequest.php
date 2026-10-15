<?php

namespace App\Plugins\Whatsapp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WhatsappCredentialRequest extends FormRequest {
    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'phone_number_id' => ['required', 'string'],
            'access_token' => ['nullable', 'string'],   // blank keeps the saved token
            'waba_id' => ['nullable', 'string'],
            'graph_version' => ['nullable', 'string'],
            'template_language' => ['required', 'string'],
            'receipt_template' => ['required', 'string'],
            'reminder_template' => ['required', 'string'],
            'fallback_to_sms' => ['required', 'boolean'],
        ];
    }
}
