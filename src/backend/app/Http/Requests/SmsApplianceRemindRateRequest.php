<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SmsApplianceRemindRateRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'appliance_type_id' => [Rule::requiredIf($this->isMethod('POST')), 'integer', 'exists:tenant.appliances,id'],
            'overdue_remind_rate' => ['required', 'integer', $this->boolean('overdue_reminder_enabled') ? 'min:1' : 'min:0'],
            'remind_rate' => ['required', 'integer', 'min:0'],
            'upcoming_reminder_enabled' => ['sometimes', 'boolean'],
            'overdue_reminder_enabled' => ['sometimes', 'boolean'],
            'create_ticket' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array {
        return [
            'appliance_type_id' => 'appliance',
            'remind_rate' => 'days before due date',
            'overdue_remind_rate' => 'days after due date',
        ];
    }
}
