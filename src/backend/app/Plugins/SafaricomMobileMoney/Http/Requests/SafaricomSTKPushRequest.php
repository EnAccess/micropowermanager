<?php

namespace App\Plugins\SafaricomMobileMoney\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SafaricomSTKPushRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'amount' => 'required|numeric|min:1|max:150000',
            'phone_number' => 'required|string|min:9|max:15',
            'device_type' => 'required|string|in:meter,solar_home_system',
            'device_serial' => 'required|string|min:3|max:100',
            'account_reference' => 'nullable|string|max:50',
            'transaction_desc' => 'nullable|string|max:50',
            'type' => 'nullable|string|in:energy,deferred_payment,down_payment',
        ];
    }

    public function messages(): array {
        return [
            'amount.min' => 'Amount must be at least 1 KES',
            'amount.max' => 'Amount cannot exceed 150,000 KES per STK Push',
        ];
    }
}
