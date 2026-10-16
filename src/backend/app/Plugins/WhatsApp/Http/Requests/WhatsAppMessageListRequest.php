<?php

namespace App\Plugins\WhatsApp\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class WhatsAppMessageListRequest extends FormRequest {
    public function authorize(): bool {
        $user = $this->user('api');
        $companyId = $this->attributes->get('companyId');

        return $user instanceof User && is_int($companyId) && (int) $user->company_id === $companyId;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array {
        $dateTo = ['sometimes', 'required', 'date_format:Y-m-d'];
        if ($this->filled('date_from')) {
            $dateTo[] = 'after_or_equal:date_from';
        }

        return [
            'page' => ['sometimes', 'required', 'integer', 'min:1', 'max:10000'],
            'per_page' => ['sometimes', 'required', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', 'required', 'string', Rule::in(['queued', 'sending', 'sent', 'delivered', 'read', 'failed'])],
            'notification_type' => ['sometimes', 'required', 'string', 'max:255'],
            'date_from' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'date_to' => $dateTo,
        ];
    }

    public function withValidator(Validator $validator): void {
        $validator->after(function (Validator $validator): void {
            if (array_diff(array_keys($this->query()), array_keys($this->rules())) !== []) {
                $validator->errors()->add('query', 'Unsupported message-history query parameters.');
            }
        });
    }
}
