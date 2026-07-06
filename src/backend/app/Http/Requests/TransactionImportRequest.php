<?php

namespace App\Http\Requests;

use App\Services\ImportServices\TransactionImportItem;
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
     * @return list<TransactionImportItem>
     */
    public function items(): array {
        return array_map(fn (array $item): TransactionImportItem => new TransactionImportItem(
            deviceId: $item['device_id'],
            amount: $this->parseAmount($item['amount']),
            customer: $item['customer'] ?? null,
            transactionType: $item['transaction_type'] ?? null,
            originalTransaction: $item['original_transaction'] ?? null,
            sentDate: $item['sent_date'] ?? null,
        ), $this->validated('data'));
    }

    /**
     * Export files carry amounts as display strings ("1,234.56 TZS") as well as plain numbers.
     */
    private function parseAmount(mixed $amount): float {
        if (is_string($amount)) {
            return (float) preg_replace('/[^0-9.]/', '', str_replace(',', '', $amount));
        }

        return is_numeric($amount) ? (float) $amount : 0.0;
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
