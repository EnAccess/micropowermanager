<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeviceRequest extends FormRequest {
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
            'id' => 'required|numeric',
            'person_id' => 'required|numeric',
            'device_type' => 'required',
            'device_serial' => 'required',
            'device_id' => 'required|numeric',
        ];
    }
}
