<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SmsApplianceRemindRateRequest extends FormRequest {
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string>
     */
    public function rules(): array {
        return [
            'appliance_type_id' => 'required',
            'overdue_remind_rate' => 'required',
            'remind_rate' => 'required',
        ];
    }
}
