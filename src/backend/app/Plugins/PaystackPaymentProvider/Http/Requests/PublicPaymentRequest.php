<?php

declare(strict_types=1);

namespace App\Plugins\PaystackPaymentProvider\Http\Requests;

use App\Plugins\PaystackPaymentProvider\Models\PaystackTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class PublicPaymentRequest extends FormRequest {
    public function authorize(): bool {
        return true; // Public endpoint, no authentication required
    }

    /**
     * @return array<string, array<int, string|int>>
     */
    public function rules(): array {
        $supportedCurrencies = config('paystack-payment-provider.currency.supported', ['NGN', 'GHS', 'KES', 'ZAR']);

        return [
            'device_serial' => ['required', 'string', 'min:3', 'max:50'],
            'device_type' => ['nullable', 'string', 'in:meter,shs,other'],
            'amount' => [
                'required',
                'numeric',
                'min:1',
                'max:1000000', // Maximum amount limit
            ],
            'currency' => [
                'required',
                'string',
                'in:'.implode(',', $supportedCurrencies),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array {
        $supportedCurrencies = config('paystack-payment-provider.currency.supported');

        return [
            'device_serial.required' => 'Device serial number is required',
            'device_serial.min' => 'Device serial number must be at least 3 characters',
            'device_serial.max' => 'Device serial number must not exceed 50 characters',
            'amount.required' => 'Payment amount is required',
            'amount.numeric' => 'Payment amount must be a valid number',
            'amount.min' => 'Payment amount must be at least 1',
            'amount.max' => 'Payment amount cannot exceed 1,000,000',
            'currency.required' => 'Currency is required',
            'currency.in' => 'Currency must be one of: '.implode(', ', $supportedCurrencies),
        ];
    }

    public function getPaystackTransaction(): PaystackTransaction {
        $validated = $this->validated();

        return new PaystackTransaction([
            'amount' => $validated['amount'],
            'currency' => $validated['currency'],
            'serial_id' => $validated['device_serial'],
            'device_type' => $validated['device_type'] ?? 'meter',
            'customer_id' => 0, // Public payment
            'order_id' => Uuid::uuid4()->toString(),
            'reference_id' => Uuid::uuid4()->toString(),
            'status' => PaystackTransaction::STATUS_REQUESTED,
        ]);
    }
}
