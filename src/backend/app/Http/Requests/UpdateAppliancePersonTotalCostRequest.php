<?php

namespace App\Http\Requests;

use App\Enums\RateType;
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
            // New number of outstanding installment rates. When given, the outstanding rates are regenerated instead of redistributed.
            'rate_count' => ['sometimes', 'integer', 'min:1'],
            // New installment schedule for regenerated rates. Without it, the plan keeps its current rate type.
            'rate_type' => ['sometimes', Rule::enum(RateType::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array {
        return [
            'rate_type.enum' => 'Rate type must be monthly or weekly',
            'rate_count.min' => 'Installment count must be at least 1',
        ];
    }
}
