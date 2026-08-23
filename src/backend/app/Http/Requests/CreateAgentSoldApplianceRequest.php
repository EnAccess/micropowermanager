<?php

namespace App\Http\Requests;

use App\Models\AgentAssignedAppliances;
use App\Models\AppliancePerson;
use App\Models\ApplianceType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class CreateAgentSoldApplianceRequest extends FormRequest {
    private ?AgentAssignedAppliances $assignedAppliance = null;
    private bool $assignedApplianceResolved = false;

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
            'agent_id' => ['nullable', 'integer'],
            'payment_type' => ['nullable', 'string', 'in:installment,energy_service'],
            'rate_type' => ['nullable', 'string', 'in:monthly,weekly'],
            // nullable as well as required_unless: an energy service sale has no installment
            // plan, and a client that sends these keys as an explicit null rather than omitting
            // them would otherwise fail the type rules that run on a present-but-null value
            'down_payment' => ['required_unless:payment_type,energy_service', 'nullable', 'numeric'],
            'tenure' => ['required_unless:payment_type,energy_service', 'nullable', 'numeric', 'min:0'],
            'first_payment_date' => ['required_unless:payment_type,energy_service', 'nullable'],
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

    public function withValidator(Validator $validator): void {
        $validator->after(function (Validator $validator): void {
            $assignedAppliance = $this->assignedAppliance();
            if (!$assignedAppliance instanceof AgentAssignedAppliances) {
                $validator->errors()->add(
                    'agent_assigned_appliance_id',
                    'The selected assigned appliance does not exist.'
                );

                return;
            }

            // The sale is attributed to the agent owning the assigned appliance, so an appliance
            // assigned to somebody else would silently book the sale on the wrong agent.
            $agentId = $this->integer('agent_id');
            if ($agentId && $assignedAppliance->agent_id !== $agentId) {
                $validator->errors()->add(
                    'agent_assigned_appliance_id',
                    'The selected appliance is not assigned to this agent.'
                );
            }

            if ($this->isEnergyService()) {
                return;
            }

            if ($this->float('down_payment') > $assignedAppliance->cost) {
                $validator->errors()->add(
                    'down_payment',
                    'Down payment is bigger than the appliance cost.'
                );
            }
        });
    }

    private function assignedAppliance(): ?AgentAssignedAppliances {
        if ($this->assignedApplianceResolved) {
            return $this->assignedAppliance;
        }

        $this->assignedApplianceResolved = true;
        $assignedApplianceId = $this->input('agent_assigned_appliance_id');
        $this->assignedAppliance = $assignedApplianceId
            ? AgentAssignedAppliances::with('appliance')->find($assignedApplianceId)
            : null;

        return $this->assignedAppliance;
    }

    private function isShsAppliance(): bool {
        return $this->assignedAppliance()?->appliance?->appliance_type_id === ApplianceType::APPLIANCE_TYPE_SHS;
    }

    private function isEnergyService(): bool {
        return $this->string('payment_type')->toString() === AppliancePerson::PAYMENT_TYPE_ENERGY_SERVICE;
    }
}
