<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApplianceRequest extends FormRequest {
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
            'appliance_type_id' => ['sometimes'],
            'name' => ['sometimes', 'min:4'],
            'price' => ['sometimes', 'numeric'],
        ];
    }
}
