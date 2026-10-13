<?php

namespace App\Plugins\WhatsappMessaging\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WhatsappCredentialRequest extends FormRequest {
    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'phone_number_id' => ['required', 'string', 'regex:/^\d+$/'],
            'business_account_id' => ['required', 'string', 'regex:/^\d+$/'],
            'access_token' => ['nullable', 'string'],
            'app_secret' => ['nullable', 'string'],
            'graph_api_version' => ['required', 'string', 'regex:/^v\d+\.\d+$/'],
            'sms_fallback_enabled' => ['required', 'boolean'],
            'sms_unit_cost' => ['nullable', 'numeric', 'min:0', 'max:999999.9999'],
            'whatsapp_utility_cost' => ['nullable', 'numeric', 'min:0', 'max:999999.9999'],
            'cost_currency' => ['nullable', 'string', 'size:3', 'alpha'],
        ];
    }
}
