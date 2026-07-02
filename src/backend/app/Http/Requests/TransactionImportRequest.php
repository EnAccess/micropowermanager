<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionImportRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'data' => ['required', 'array', 'list'],
            'data.*.device_id' => ['required', 'string', 'min:1'],
            'data.*.amount' => ['required'],
            'data.*.customer' => ['sometimes', 'nullable', 'string'],
            'data.*.transaction_type' => ['sometimes', 'nullable', 'string'],
            'data.*.original_transaction' => ['sometimes', 'nullable', 'array'],
            'data.*.sent_date' => ['sometimes', 'nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array {
        return [
            'data.required' => 'The data field is required.',
            'data.array' => 'The data must be an array.',
            'data.*.device_id.required' => 'Each transaction must have a device serial number.',
            'data.*.amount.required' => 'Each transaction must have an amount.',
        ];
    }
}
