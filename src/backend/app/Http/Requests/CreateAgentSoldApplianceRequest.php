<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAgentSoldApplianceRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string>
     */
    public function rules(): array {
        return [
            'person_id' => 'required',
            'down_payment' => 'required|numeric',
            'tenure' => 'required|numeric|min:0',
            'first_payment_date' => 'required',
            'agent_assigned_appliance_id' => 'required',
            'device_serial' => 'nullable|string',
            'address' => 'nullable|array',
            'points' => 'nullable|string',
        ];
    }
}
