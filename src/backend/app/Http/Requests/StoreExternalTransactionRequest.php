<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExternalTransactionRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            // Device (appliance, meter, or SHS) serial the payment is for.
            'serial' => ['required', 'string'],
            'amount' => ['required', 'numeric'],
            // The external party's own transaction ID; repeating it returns the original result.
            'external_reference' => ['required', 'string'],
        ];
    }
}
