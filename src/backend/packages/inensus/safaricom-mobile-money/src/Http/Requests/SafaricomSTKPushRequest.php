<?php

namespace Inensus\SafaricomMobileMoney\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SafaricomSTKPushRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'amount' => 'required|numeric|min:1|max:150000',
            'phone_number' => 'required|string|regex:/^254[0-9]{9}$/',
            'account_reference' => 'nullable|string|max:50',
            'transaction_desc' => 'nullable|string|max:50',
        ];
    }

    public function messages(): array {
        return [
            'phone_number.regex' => 'Phone number must be in format 254XXXXXXXXX',
            'amount.min' => 'Amount must be at least 1 KES',
            'amount.max' => 'Amount cannot exceed 150,000 KES',
        ];
    }
}
