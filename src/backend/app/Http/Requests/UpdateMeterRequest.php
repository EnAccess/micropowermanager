<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMeterRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules() {
        return [
            'id' => 'required|numeric',
            'serial_number' => 'sometimes|string',
            'meter_type_id' => 'sometimes|numeric',
            'in_use' => 'sometimes|numeric',
            'manufacturer_id' => 'sometimes|numeric',
            'connection_type_id' => 'sometimes|numeric',
            'connection_group_id' => 'sometimes|numeric',
            'tariff_id' => 'sometimes|numeric',
        ];
    }
}
