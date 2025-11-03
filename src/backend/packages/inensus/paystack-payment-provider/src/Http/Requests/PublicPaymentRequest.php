<?php

declare(strict_types=1);

namespace Inensus\PaystackPaymentProvider\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Inensus\PaystackPaymentProvider\Models\PaystackTransaction;
use Ramsey\Uuid\Uuid;

class PublicPaymentRequest extends FormRequest {
    public function authorize(): bool {
        return true; // Public endpoint, no authentication required
    }

    /**
     * @return array<string, array<int, string|int>>
     */
    public function rules(): array {
        return [
            'meter_serial' => ['nullable', 'string', 'min:3', 'max:50'],
            'serial' => ['required_without:meter_serial', 'string', 'min:3', 'max:50'],
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
                'in:NGN,GHS,KES,ZAR',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array {
        return [
            'meter_serial.required' => 'Meter serial number is required',
            'meter_serial.min' => 'Meter serial number must be at least 3 characters',
            'meter_serial.max' => 'Meter serial number must not exceed 50 characters',
            'amount.required' => 'Payment amount is required',
            'amount.numeric' => 'Payment amount must be a valid number',
            'amount.min' => 'Payment amount must be at least 1',
            'amount.max' => 'Payment amount cannot exceed 1,000,000',
            'currency.required' => 'Currency is required',
            'currency.in' => 'Currency must be one of: NGN, GHS, KES, ZAR',
        ];
    }

    public function getPaystackTransaction(): PaystackTransaction {
        $validated = $this->validated();

        $serial = $validated['serial'] ?? $validated['meter_serial'];
        $deviceType = $validated['device_type'] ?? 'meter';

        return new PaystackTransaction([
            'amount' => $validated['amount'],
            'currency' => $validated['currency'],
            'serial_id' => $serial,
            'device_type' => $deviceType,
            'customer_id' => 0, // Public payment
            'order_id' => Uuid::uuid4()->toString(),
            'reference_id' => Uuid::uuid4()->toString(),
            'status' => PaystackTransaction::STATUS_REQUESTED,
        ]);
    }
}
