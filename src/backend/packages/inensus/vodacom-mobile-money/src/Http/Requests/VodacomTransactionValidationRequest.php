<?php

namespace Inensus\VodacomMobileMoney\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VodacomTransactionValidationRequest extends FormRequest {
    public function authorize(): bool {
        return true; // Set this based on your authentication logic if needed
    }

    public function rules(): array {
        return [
            'serialNumber' => 'required|string|regex:/^[A-Z0-9]{8,12}$/',
            'amount' => 'required|numeric|min:100|max:5000000',
            'payerPhoneNumber' => 'required|string|regex:/^258[0-9]{9}$/',
            'referenceId' => 'required|string|regex:/^[A-Za-z0-9\-]{5,20}$/',
        ];
    }
}
