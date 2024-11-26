<?php

namespace Inensus\WaveMoneyPaymentProvider\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WaveMoneyCredentialRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'merchant_id' => ['required'],
            'secret_key' => ['required'],
        ];
    }
}
