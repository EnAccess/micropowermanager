<?php

namespace App\Plugins\WaveMoneyPaymentProvider\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WaveMoneyCredentialRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'merchant_id' => ['required'],
            'secret_key' => ['required'],
        ];
    }
}
