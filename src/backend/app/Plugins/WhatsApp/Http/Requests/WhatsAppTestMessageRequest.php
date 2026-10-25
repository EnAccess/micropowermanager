<?php

namespace App\Plugins\WhatsApp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WhatsAppTestMessageRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'recipient_phone' => ['required', 'string'],
            'template_id' => ['nullable', 'integer', 'exists:tenant.whatsapp_templates,id'],
            'message' => ['nullable', 'string', 'required_without:template_id'],
        ];
    }
}
