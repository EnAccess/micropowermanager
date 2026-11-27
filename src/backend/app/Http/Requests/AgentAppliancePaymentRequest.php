<?php

namespace App\Http\Requests;

use App\Enums\PaymentInitiationProvider;
use Illuminate\Foundation\Http\FormRequest;

class AgentAppliancePaymentRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'amount' => ['required', 'numeric'],
            'payment_provider' => ['sometimes', 'nullable', PaymentInitiationProvider::validationRule()],
            'payer_phone' => ['sometimes', 'string', 'phone:INTERNATIONAL'],
        ];
    }
}
