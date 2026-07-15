<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppliancePersonTotalCostRequest extends FormRequest {
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
            // New total cost of the sold appliance.
            'new_total_cost' => ['required', 'integer', 'min:0'],
            // ID of the MPM user performing the change; recorded in the activity log.
            'admin_id' => ['required', 'integer'],
            // New number of outstanding installment rates. When given (together with `rate_type`), the outstanding rates are regenerated instead of redistributed.
            'rate_count' => ['sometimes', 'integer', 'min:1'],
            // New installment schedule for regenerated rates.
            'rate_type' => ['sometimes', Rule::in(['monthly', 'weekly'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array {
        return [
            'rate_type.in' => 'Rate type must be monthly or weekly',
            'rate_count.min' => 'Installment count must be at least 1',
        ];
    }
}
