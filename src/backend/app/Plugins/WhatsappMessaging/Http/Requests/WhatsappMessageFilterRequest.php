<?php

namespace App\Plugins\WhatsappMessaging\Http\Requests;

use App\Plugins\WhatsappMessaging\Models\WhatsappMessage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WhatsappMessageFilterRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'status' => ['nullable', Rule::in([
                WhatsappMessage::STATUS_ACCEPTED,
                WhatsappMessage::STATUS_SENT,
                WhatsappMessage::STATUS_DELIVERED,
                WhatsappMessage::STATUS_READ,
                WhatsappMessage::STATUS_FAILED,
            ])],
            'phone' => ['nullable', 'string', 'max:32'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
