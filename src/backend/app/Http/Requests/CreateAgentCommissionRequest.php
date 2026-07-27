<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAgentCommissionRequest extends FormRequest {
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
            'name' => ['required'],
            'energy_commission' => ['required', 'numeric', 'between:0,1'],
            'appliance_commission' => ['required', 'numeric', 'between:0,1'],
            'risk_balance' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array {
        return [
            'energy_commission.between' => 'The energy commission must be a fraction between 0 and 1 (e.g. 0.1 for 10%).',
            'appliance_commission.between' => 'The appliance commission must be a fraction between 0 and 1 (e.g. 0.1 for 10%).',
        ];
    }
}
