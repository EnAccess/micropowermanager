<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAgentCommissionRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules() {
        return [
            'name' => 'required',
            'energy_commission' => 'required|numeric',
            'appliance_commission' => 'required|numeric',
            'risk_balance' => 'required|numeric|max:0',
        ];
    }
}
