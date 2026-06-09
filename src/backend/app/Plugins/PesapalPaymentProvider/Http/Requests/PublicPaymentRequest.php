<?php

declare(strict_types=1);

namespace App\Plugins\PesapalPaymentProvider\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublicPaymentRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, array<int, string|int>>
     */
    public function rules(): array {
        return [
            'device_serial' => ['required', 'string', 'min:3', 'max:50'],
            'device_type' => ['nullable', 'string', 'in:meter,shs,other'],
            'amount' => [
                'required',
                'numeric',
                'min:1',
                'max:1000000',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array {
        return [
            'device_serial.required' => 'Device serial number is required',
            'device_serial.min' => 'Device serial number must be at least 3 characters',
            'device_serial.max' => 'Device serial number must not exceed 50 characters',
            'amount.required' => 'Payment amount is required',
            'amount.numeric' => 'Payment amount must be a valid number',
            'amount.min' => 'Payment amount must be at least 1',
            'amount.max' => 'Payment amount cannot exceed 1,000,000',
        ];
    }
}
