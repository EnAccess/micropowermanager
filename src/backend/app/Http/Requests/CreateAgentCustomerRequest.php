<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAgentCustomerRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array {
        return [
            'name' => ['required', 'string', 'min:3'],
            'surname' => ['required', 'string', 'min:3'],
            'phone' => ['required', 'string', 'phone:INTERNATIONAL'],
            'city_id' => ['required', 'integer', 'exists:tenant.cities,id'],
            'geo_points' => ['nullable', 'string'],
        ];
    }
}
