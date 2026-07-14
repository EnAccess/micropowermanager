<?php

namespace App\Plugins\SafaricomKePaymentProvider\Http\Requests;

use App\Enums\DeviceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SafaricomSTKPushRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array {
        return [
            'amount' => ['required', 'numeric', 'min:1', 'max:150000'],
            'phone_number' => ['required', 'string', 'min:9', 'max:15'],
            'device_type' => ['required', 'string', Rule::in([DeviceType::Meter->value, DeviceType::SolarHomeSystem->value])],
            'device_serial' => ['required', 'string', 'min:3', 'max:100'],
            'account_reference' => ['nullable', 'string', 'max:50'],
            'transaction_desc' => ['nullable', 'string', 'max:50'],
            'type' => ['nullable', 'string', 'in:energy,deferred_payment,down_payment'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array {
        return [
            'amount.min' => 'Amount must be at least 1 KES',
            'amount.max' => 'Amount cannot exceed 150,000 KES per STK Push',
        ];
    }
}
