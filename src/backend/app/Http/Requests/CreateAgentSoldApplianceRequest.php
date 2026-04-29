<?php

namespace App\Http\Requests;

use App\Models\AgentAssignedAppliances;
use App\Models\ApplianceType;
use Illuminate\Foundation\Http\FormRequest;

class CreateAgentSoldApplianceRequest extends FormRequest {
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
        $deviceSerialRules = $this->isShsAppliance()
            ? ['required', 'string']
            : ['nullable', 'string'];

        return [
            'person_id' => ['required'],
            'payment_type' => ['nullable', 'string', 'in:installment,energy_service'],
            'down_payment' => ['required_unless:payment_type,energy_service', 'numeric'],
            'tenure' => ['required_unless:payment_type,energy_service', 'numeric', 'min:0'],
            'first_payment_date' => ['required_unless:payment_type,energy_service'],
            'agent_assigned_appliance_id' => ['required'],
            'device_serial' => $deviceSerialRules,
            'address' => ['nullable', 'array'],
            'points' => ['nullable', 'string'],
            'minimum_payable_amount' => ['nullable', 'integer', 'min:0'],
            'price_per_day' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array {
        return [
            'device_serial.required' => 'Device serial is required for solar home system sales.',
        ];
    }

    private function isShsAppliance(): bool {
        $assignedId = $this->input('agent_assigned_appliance_id');
        if (!$assignedId) {
            return false;
        }

        $assigned = AgentAssignedAppliances::with('appliance')->find($assignedId);

        return $assigned?->appliance?->appliance_type_id === ApplianceType::APPLIANCE_TYPE_SHS;
    }
}
