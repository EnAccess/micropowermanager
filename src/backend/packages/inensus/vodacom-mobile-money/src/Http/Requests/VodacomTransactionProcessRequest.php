<?php

namespace Inensus\VodacomMobileMoney\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VodacomTransactionProcessRequest extends FormRequest {
    public function authorize(): bool {
        return true; // Set this based on your authentication logic if needed
    }

    public function rules(): array {
        return [
            'referenceId' => 'required|string|regex:/^[A-Za-z0-9\-]{5,20}$/',
            'transactionId' => 'required|string|regex:/^VOD-TXN-[0-9]{6}$/',
        ];
    }
}
