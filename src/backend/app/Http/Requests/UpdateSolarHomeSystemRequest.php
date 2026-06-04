<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSolarHomeSystemRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array {
        return [
            'manufacturer_id' => ['required', 'exists:tenant.manufacturers,id'],
            'appliance_id' => ['required', 'exists:tenant.appliances,id'],
        ];
    }
}
