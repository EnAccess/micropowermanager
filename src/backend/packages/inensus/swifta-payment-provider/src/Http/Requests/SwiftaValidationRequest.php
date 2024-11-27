<?php

namespace Inensus\SwiftaPaymentProvider\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SwiftaValidationRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'meter_number' => 'required',
            'amount' => 'required',
            'cipher' => 'required',
            'timestamp' => 'required',
        ];
    }
}
