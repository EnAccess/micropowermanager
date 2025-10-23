<?php

namespace Inensus\VodacomMobileMoney\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VodacomTransactionEnquiryStatusRequest extends FormRequest {
    public function authorize(): bool {
        return true; // Set this based on your authentication logic if needed
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'referenceId' => 'required|string|regex:/^[A-Za-z0-9\-]{5,20}$/',
        ];
    }
}
