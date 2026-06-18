<?php

namespace App\Plugins\VodacomMzPaymentProvider\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VodacomTransactionValidationRequest extends FormRequest {
    public function authorize(): bool {
        return true; // Set this based on your authentication logic if needed
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            // `reference` has to match an existing Device's serial number else the transaction gets rejected.
            'reference' => ['required', 'string', 'regex:/^[A-Z0-9]{8,12}$/'],
            // Transaction `amount` in MZN (Mozambican metical)
            'amount' => ['required', 'numeric', 'min:1', 'max:5000000'],
            'request_id' => ['required', 'uuid'],
        ];
    }
}
