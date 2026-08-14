<?php

declare(strict_types=1);

namespace app\Plugins\FlutterwavePaymentProvider\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array {
        return [
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['string', Rule::in(config('flutterwave-payment-provider.currency.supported'))],

            /*
             * 0 = Requested - initial state when the transaction is created, before payment is confirmed.
             * 1 = Success - payment confirmed.
             * 2 = Completed.
             * 3 = Abandoned.
             */
            'status' => ['sometimes', 'integer', 'in:0,1,2,3'],
        ];
    }

    public function messages(): array {
        return [
            'amount.required' => 'The amount field is required.',
            'amount.numeric' => 'The amount must be a number.',
            'amount.min' => 'The amount must be at least 0.',
            'currency.string' => 'The currency must be a string.',
            'currency.in' => 'The currency must be one of the following: '.implode(', ', config('flutterwave-payment-provider.currency.supported')),
            'status.sometimes' => 'The status field is sometimes required.',
            'status.in' => 'The status must be one of the following: 0,1,2,3.',
        ];
    }
}
