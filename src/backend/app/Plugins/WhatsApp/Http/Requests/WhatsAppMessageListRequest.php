<?php

namespace App\Plugins\WhatsApp\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class WhatsAppMessageListRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array {
        $dateTo = ['sometimes', 'nullable', 'date_format:Y-m-d'];
        if ($this->filled('date_from')) {
            $dateTo[] = 'after_or_equal:date_from';
        }

        return [
            'page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:10000'],
            'per_page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', 'nullable', 'string'],
            'notification_type' => ['sometimes', 'nullable', 'string', 'max:255'],
            'date_from' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'date_to' => $dateTo,
        ];
    }
}
