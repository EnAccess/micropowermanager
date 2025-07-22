<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManufacturerRequest extends FormRequest {
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
            'name' => 'required|string',
            'phone' => 'sometimes|string',
            'email' => 'sometimes|email',
            'contact_person' => 'sometimes|min:3',
            'website' => 'sometimes|min:6',
            'city_id' => 'sometimes|integer',
            'address' => 'sometimes|string',
            'api_name' => 'sometimes',
        ];
    }
}
