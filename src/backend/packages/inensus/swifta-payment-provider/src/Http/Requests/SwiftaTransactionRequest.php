<?php

namespace Inensus\SwiftaPaymentProvider\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SwiftaTransactionRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'transaction_id' => 'required',
            'transaction_reference' => 'required',
            'cipher' => 'required',
            'amount' => 'required',
            'timestamp' => 'required',
        ];
    }
}
